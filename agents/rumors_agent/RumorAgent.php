<?php
/**
 * RumorAgent for Valley by Night
 *
 * Location suggestion: /agents/rumors_agent/RumorAgent.php
 *
 * This class is responsible for selecting nightly rumors for a given character
 * using the `rumors` table in the database, based on the Rumor Template JSON
 * structure you designed for VbN.
 *
 * Integration notes:
 * - Uses Supabase (includes/supabase_client.php). $db param ignored.
 * - Uses rumors and character_heard_rumors tables.
 * - Designed to be called from an Agent page in the admin panel.
 *
 * Assumed table structures (adjust as needed):
 *
 * `rumors`:
 *   id INT PK
 *   title VARCHAR
 *   rumor_text TEXT
 *   truth_rating INT
 *   source_type VARCHAR
 *   clan_tags TEXT         (comma-separated list, e.g. "Nosferatu,Gangrel")
 *   location_tags TEXT     (comma-separated list, e.g. "Phoenix,Scottsdale")
 *   connects_to_plot_ids TEXT (comma-separated: "phoenix_giovanni_01,setites_02")
 *   danger_rating INT
 *   spread_likelihood VARCHAR (e.g. "low","medium","high" or numeric string)
 *   visibility VARCHAR     ("public","secret","gm-only",etc.)
 *   nighttime_trigger_flags TEXT
 *   storyteller_notes TEXT
 *
 * `character_heard_rumors`:
 *   id INT PK
 *   character_id INT
 *   rumor_id INT (FK to rumors.id)
 *   heard_on DATETIME
 *
 * Character context is passed in from the calling code (Agent page), typically
 * using data from the `characters` table:
 *   $characterContext = [
 *       'id'              => 123,
 *       'clan'            => 'Nosferatu',
 *       'location_tag'    => 'Phoenix', // or domain/haven region
 *       'backgrounds'     => ['Allies' => 2, 'Contacts' => 3, 'Influence' => 1],
 *   ];
 */

class RumorAgent
{
    /** @var mixed Legacy; ignored. Uses Supabase. */
    protected $db;

    public function __construct($db = null)
    {
        $this->db = null;
        require_once __DIR__ . '/../../includes/supabase_client.php';
    }

    /**
     * Main entry point to get nightly rumors for a character.
     *
     * Behavior, based on your requirements:
     * - Avoids rumors the character has already heard (DB-backed).
     * - Weights rumors more heavily if:
     *      - spread_likelihood is higher (low/medium/high).
     *      - rumor connects to an active plot id.
     *      - rumor has clan/location tags matching the character (light filter).
     * - Does NOT weight by truth_rating.
     *
     * @param int   $characterId      DB id for the character.
     * @param array $characterContext Array with clan, location_tag, backgrounds, etc.
     * @param array $activePlotIds    Array of active plot ids this night.
     * @param int   $limit            Number of rumors to draw.
     * @return array                  Array of rumor rows (assoc arrays from `rumors`).
     */
    public function getRumorsForCharacter(int $characterId, array $characterContext = [], array $activePlotIds = [], int $limit = 3): array
    {
        $heardIds   = $this->getHeardRumorIds($characterId);
        $candidates = $this->loadCandidateRumors($heardIds);

        if (empty($candidates)) {
            return [];
        }

        $weightedRumors = $this->applyWeights($candidates, $characterContext, $activePlotIds);

        if (empty($weightedRumors)) {
            return [];
        }

        $selected = $this->pickWeightedRandomRumors($weightedRumors, $limit);

        // Persist that the character has now heard these rumors
        $this->recordRumorsHeard($characterId, $selected);

        return $selected;
    }

    /**
     * Returns an array of rumor_id values the character has already heard.
     *
     * @param int $characterId
     * @return int[]
     */
    protected function getHeardRumorIds(int $characterId): array
    {
        $rows = supabase_table_get('character_heard_rumors', [
            'select' => 'rumor_id',
            'character_id' => 'eq.' . $characterId
        ]);
        return array_map(static fn($r) => (int)($r['rumor_id'] ?? 0), is_array($rows) ? $rows : []);
    }

    /**
     * Load candidate rumors from the DB, excluding those already heard
     * and GM-only visibility.
     *
     * @param int[] $excludeIds
     * @return array
     */
    protected function loadCandidateRumors(array $excludeIds = []): array
    {
        $query = [
            'select' => 'id,title,rumor_text,truth_rating,source_type,clan_tags,location_tags,connects_to_plot_ids,danger_rating,spread_likelihood,visibility,nighttime_trigger_flags,storyteller_notes',
            'or' => '(visibility.is.null,visibility.neq.gm-only)'
        ];
        if (!empty($excludeIds)) {
            $safeIds = array_map('intval', $excludeIds);
            $query['id'] = 'not.in.(' . implode(',', $safeIds) . ')';
        }
        $rows = supabase_table_get('rumors', $query);
        return is_array($rows) ? $rows : [];
    }

    /**
     * Apply weighting rules to candidate rumors.
     *
     * - Base weight comes from spread_likelihood (low/medium/high or numeric).
     * - + clan/location relevance (light filtering).
     * - *2 if connects_to_plot_ids intersects with active plot ids.
     *
     * Returns an array keyed by rumor id:
     *  [
     *      10 => ['weight' => 5, 'row' => [ ... rumor data ... ]],
     *      11 => ['weight' => 1, 'row' => [ ... ]],
     *  ]
     *
     * @param array $candidates
     * @param array $characterContext
     * @param array $activePlotIds
     * @return array
     */
    protected function applyWeights(array $candidates, array $characterContext, array $activePlotIds): array
    {
        $weighted = [];

        $charClan    = isset($characterContext['clan']) ? strtolower(trim($characterContext['clan'])) : null;
        $charLoc     = isset($characterContext['location_tag']) ? strtolower(trim($characterContext['location_tag'])) : null;
        $backgrounds = isset($characterContext['backgrounds']) && is_array($characterContext['backgrounds'])
            ? $characterContext['backgrounds']
            : [];

        $activePlotIdsLower = array_map('strtolower', $activePlotIds);

        foreach ($candidates as $row) {
            $id = (int) $row['id'];

            // Base weight from spread_likelihood
            $spreadRaw = $row['spread_likelihood'] ?? null;
            $weight    = $this->spreadLikelihoodToWeight($spreadRaw);

            // Light clan relevance: if clan tag matches, bump weight
            if ($charClan !== null && !empty($row['clan_tags'])) {
                $tags = $this->explodeTags($row['clan_tags']);
                if (in_array($charClan, $tags, true)) {
                    $weight += 2; // clan match bonus
                }
            }

            // Light location relevance
            if ($charLoc !== null && !empty($row['location_tags'])) {
                $locTags = $this->explodeTags($row['location_tags']);
                if (in_array($charLoc, $locTags, true)) {
                    $weight += 1; // local rumor bump
                }
            }

            // Backgrounds can give a small global bump if the rumor has a source_type
            if (!empty($backgrounds) && !empty($row['source_type'])) {
                $source = strtolower($row['source_type']);
                // Simple example: if character has Contacts or Allies, they get a slight bonus
                $hasSocialNet = !empty($backgrounds['Allies']) || !empty($backgrounds['Contacts']) || !empty($backgrounds['Influence']);
                if ($hasSocialNet) {
                    $weight += 1;
                }
            }

            // Plot-linked rumors: prioritize if connects_to_plot_ids hits an active plot
            if (!empty($activePlotIdsLower) && !empty($row['connects_to_plot_ids'])) {
                $plotTags = $this->explodeTags($row['connects_to_plot_ids']);
                $hit      = array_intersect($activePlotIdsLower, $plotTags);
                if (!empty($hit)) {
                    $weight *= 2;
                }
            }

            // Ensure minimum weight of 1 to keep it in the pool
            if ($weight < 1) {
                $weight = 1;
            }

            $weighted[$id] = [
                'weight' => $weight,
                'row'    => $row,
            ];
        }

        return $weighted;
    }

    /**
     * Convert spread_likelihood to a numeric weight.
     * Accepts textual ("low","medium","high") or numeric strings.
     *
     * @param mixed $spread
     * @return int
     */
    protected function spreadLikelihoodToWeight($spread): int
    {
        if ($spread === null || $spread === '') {
            return 1;
        }

        // If numeric, use it directly but clamp
        if (is_numeric($spread)) {
            $val = (int) $spread;
            if ($val < 1) {
                $val = 1;
            }
            if ($val > 5) {
                $val = 5;
            }
            return $val;
        }

        // Normalize text
        $normalized = strtolower(trim((string) $spread));

        switch ($normalized) {
            case 'low':
                return 1;
            case 'medium':
            case 'med':
                return 2;
            case 'high':
                return 3;
            default:
                return 1;
        }
    }

    /**
     * Utility to break comma/semicolon separated tag strings into a lowercase array.
     *
     * @param string $str
     * @return array
     */
    protected function explodeTags(string $str): array
    {
        $parts = preg_split('/[,;]+/', $str);
        $tags  = [];

        foreach ($parts as $p) {
            $t = strtolower(trim($p));
            if ($t !== '') {
                $tags[] = $t;
            }
        }

        return $tags;
    }

    /**
     * Pick up to $limit rumors from a weighted pool without replacement.
     *
     * @param array $weightedRumors Output of applyWeights().
     * @param int   $limit
     * @return array                Array of raw rumor rows (assoc arrays).
     */
    protected function pickWeightedRandomRumors(array $weightedRumors, int $limit): array
    {
        $selected = [];
        $limit    = max(1, $limit);

        // Work on a local copy
        $pool = $weightedRumors;

        for ($i = 0; $i < $limit && !empty($pool); $i++) {
            $totalWeight = 0;
            foreach ($pool as $entry) {
                $totalWeight += $entry['weight'];
            }

            if ($totalWeight <= 0) {
                break;
            }

            $rand    = mt_rand(1, $totalWeight);
            $running = 0;
            $chosenId = null;

            foreach ($pool as $id => $entry) {
                $running += $entry['weight'];
                if ($rand <= $running) {
                    $chosenId = $id;
                    break;
                }
            }

            if ($chosenId === null) {
                break;
            }

            $selected[] = $pool[$chosenId]['row'];
            unset($pool[$chosenId]);
        }

        return $selected;
    }

    /**
     * Record that a character has heard the selected rumors.
     *
     * @param int   $characterId
     * @param array $rumors  Array of rumor rows with 'id' key.
     * @return void
     */
    protected function recordRumorsHeard(int $characterId, array $rumors): void
    {
        if (empty($rumors)) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        foreach ($rumors as $row) {
            if (!isset($row['id'])) continue;
            $rid = (int)$row['id'];
            supabase_rest_request('POST', '/rest/v1/character_heard_rumors', [], [
                'character_id' => $characterId,
                'rumor_id' => $rid,
                'heard_on' => $now
            ], ['Prefer: return=minimal']);
        }
    }

    /**
     * Render rumors as simple HTML for the Agent page.
     *
     * @param array $rumors
     * @return string
     */
    public function renderRumorsAsHtml(array $rumors): string
    {
        if (empty($rumors)) {
            return '<p>No new rumors are circulating tonight.</p>';
        }

        $html = '<div class="rumor-agent-results">';
        $html .= '<h3>Rumors for Tonight</h3>';
        $html .= '<ul class="rumor-list">';

        foreach ($rumors as $row) {
            $title = htmlspecialchars($row['title'] ?? 'Untitled Rumor', ENT_QUOTES, 'UTF-8');
            $text  = htmlspecialchars($row['rumor_text'] ?? '', ENT_QUOTES, 'UTF-8');

            $html .= '<li class="rumor-item">';
            $html .= '<strong class="rumor-title">' . $title . '</strong><br>';
            $html .= '<span class="rumor-text">' . nl2br($text) . '</span>';
            $html .= '</li>';
        }

        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }
}
