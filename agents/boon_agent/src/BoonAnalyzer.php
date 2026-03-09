<?php
/**
 * BoonAnalyzer
 * Monitors boons and detects various issues and patterns (Supabase)
 */

class BoonAnalyzer
{
    /** @var mixed Legacy; ignored. Uses Supabase. */
    protected $db;

    /** @var array */
    protected $config;

    public function __construct($db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    protected function supabase(): void
    {
        static $loaded = false;
        if (!$loaded) {
            require_once __DIR__ . '/../../../includes/supabase_client.php';
            $loaded = true;
        }
    }

    protected function fetchBoonsWithNames(array $query): array
    {
        $this->supabase();
        $rows = supabase_table_get('boons', $query);
        if (empty($rows)) {
            return [];
        }
        $charIds = [];
        foreach ($rows as $r) {
            if (!empty($r['creditor_id'])) $charIds[(int)$r['creditor_id']] = true;
            if (!empty($r['debtor_id'])) $charIds[(int)$r['debtor_id']] = true;
        }
        $charIds = array_keys($charIds);
        $nameMap = [];
        if (!empty($charIds)) {
            $chars = supabase_table_get('characters', ['select' => 'id,character_name,status', 'id' => 'in.(' . implode(',', $charIds) . ')']);
            foreach ($chars as $c) {
                $nameMap[(int)$c['id']] = ['name' => $c['character_name'] ?? '', 'status' => $c['status'] ?? ''];
            }
        }
        $out = [];
        foreach ($rows as $r) {
            $cid = (int)($r['creditor_id'] ?? 0);
            $did = (int)($r['debtor_id'] ?? 0);
            $r['giver_name'] = $nameMap[$cid]['name'] ?? '';
            $r['receiver_name'] = $nameMap[$did]['name'] ?? '';
            $r['character_status'] = $nameMap[$did]['status'] ?? null;
            $r['boon_id'] = $r['id'];
            $r['date_created'] = $r['created_date'] ?? null;
            $r['boon_type'] = ucfirst(strtolower((string)($r['boon_type'] ?? '')));
            $out[] = $r;
        }
        return $out;
    }

    public function findDeadDebts(): array
    {
        $this->supabase();
        $deadChars = supabase_table_get('characters', ['select' => 'id', 'status' => 'ilike.dead']);
        $deadIds = array_column($deadChars, 'id');
        if (empty($deadIds)) {
            return ['success' => true, 'count' => 0, 'dead_debts' => [], 'message' => ''];
        }
        $deadDebts = $this->fetchBoonsWithNames([
            'select' => 'id,creditor_id,debtor_id,boon_type,status,description,created_date',
            'status' => 'eq.active',
            'debtor_id' => 'in.(' . implode(',', array_map('intval', $deadIds)) . ')',
            'order' => 'created_date.desc'
        ]);
        return [
            'success' => true,
            'count' => count($deadDebts),
            'dead_debts' => $deadDebts
        ];
    }

    public function findUnregisteredBoons(): array
    {
        $this->supabase();
        $all = $this->fetchBoonsWithNames([
            'select' => 'id,creditor_id,debtor_id,boon_type,status,description,created_date,registered_with_harpy',
            'status' => 'eq.active',
            'order' => 'created_date.desc'
        ]);
        $unregistered = array_filter($all, static function ($r) {
            $reg = $r['registered_with_harpy'] ?? null;
            return $reg === null || $reg === '';
        });
        return [
            'success' => true,
            'count' => count($unregistered),
            'unregistered' => array_values($unregistered)
        ];
    }

    public function findBrokenBoons(): array
    {
        $this->supabase();
        $broken = $this->fetchBoonsWithNames([
            'select' => 'id,creditor_id,debtor_id,boon_type,status,description,created_date,harpy_notes',
            'or' => '(status.eq.disputed,status.eq.cancelled)',
            'order' => 'created_date.desc'
        ]);
        return [
            'success' => true,
            'count' => count($broken),
            'broken' => $broken
        ];
    }

    public function findCombinationOpportunities(): array
    {
        $this->supabase();
        $rows = supabase_table_get('boons', [
            'select' => 'id,creditor_id,debtor_id,boon_type',
            'status' => 'eq.active'
        ]);
        $groups = [];
        foreach ($rows as $r) {
            $key = ((int)($r['creditor_id'] ?? 0)) . '_' . ((int)($r['debtor_id'] ?? 0));
            if (!isset($groups[$key])) {
                $groups[$key] = ['creditor_id' => $r['creditor_id'], 'debtor_id' => $r['debtor_id'], 'boons' => [], 'types' => []];
            }
            $groups[$key]['boons'][] = $r['id'];
            $groups[$key]['types'][] = ucfirst(strtolower((string)($r['boon_type'] ?? '')));
        }
        $opportunities = [];
        foreach ($groups as $g) {
            if (count($g['boons']) < 2) continue;
            $charIds = array_filter([$g['creditor_id'], $g['debtor_id']]);
            $nameMap = [];
            if (!empty($charIds)) {
                $chars = supabase_table_get('characters', ['select' => 'id,character_name', 'id' => 'in.(' . implode(',', array_map('intval', $charIds)) . ')']);
                foreach ($chars as $c) {
                    $nameMap[(int)$c['id']] = $c['character_name'] ?? '';
                }
            }
            $opportunities[] = [
                'creditor_id' => $g['creditor_id'],
                'debtor_id' => $g['debtor_id'],
                'giver_name' => $nameMap[(int)$g['creditor_id']] ?? '',
                'receiver_name' => $nameMap[(int)$g['debtor_id']] ?? '',
                'boon_count' => count($g['boons']),
                'boon_ids' => $g['boons'],
                'boon_types' => $g['types']
            ];
        }
        usort($opportunities, static fn($a, $b) => $b['boon_count'] <=> $a['boon_count']);
        return [
            'success' => true,
            'count' => count($opportunities),
            'opportunities' => $opportunities
        ];
    }

    public function analyzeEconomy(): array
    {
        $this->supabase();
        $rows = supabase_table_get('boons', [
            'select' => 'id,creditor_id,debtor_id,boon_type,status'
        ]);
        $charIds = [];
        foreach ($rows as $r) {
            if (!empty($r['creditor_id'])) $charIds[(int)$r['creditor_id']] = true;
            if (!empty($r['debtor_id'])) $charIds[(int)$r['debtor_id']] = true;
        }
        $charIds = array_keys($charIds);
        $clanMap = [];
        $nameMap = [];
        if (!empty($charIds)) {
            $chars = supabase_table_get('characters', ['select' => 'id,character_name,clan', 'id' => 'in.(' . implode(',', $charIds) . ')']);
            foreach ($chars as $c) {
                $id = (int)$c['id'];
                $nameMap[$id] = $c['character_name'] ?? '';
                $clanMap[$id] = $c['clan'] ?? 'Unknown';
            }
        }
        $boons = [];
        foreach ($rows as $r) {
            $r['boon_id'] = $r['id'];
            $r['giver_name'] = $nameMap[(int)($r['creditor_id'] ?? 0)] ?? '';
            $r['receiver_name'] = $nameMap[(int)($r['debtor_id'] ?? 0)] ?? '';
            $r['creditor_clan'] = $clanMap[(int)($r['creditor_id'] ?? 0)] ?? 'Unknown';
            $r['debtor_clan'] = $clanMap[(int)($r['debtor_id'] ?? 0)] ?? 'Unknown';
            $boons[] = $r;
        }
        $analysis = [
            'total_boons' => count($boons),
            'by_status' => [],
            'by_type' => [],
            'top_creditors' => [],
            'top_debtors' => [],
            'by_clan' => []
        ];
        $statusMap = ['active' => 'Active/Owed', 'fulfilled' => 'Fulfilled/Paid', 'cancelled' => 'Cancelled', 'disputed' => 'Disputed/Broken'];
        foreach ($boons as $b) {
            $st = strtolower((string)($b['status'] ?? 'unknown'));
            $label = $statusMap[$st] ?? ucfirst($st);
            $analysis['by_status'][$label] = ($analysis['by_status'][$label] ?? 0) + 1;
        }
        foreach ($boons as $b) {
            $tp = ucfirst(strtolower((string)($b['boon_type'] ?? 'unknown')));
            $analysis['by_type'][$tp] = ($analysis['by_type'][$tp] ?? 0) + 1;
        }
        $creditors = [];
        $debtors = [];
        foreach ($boons as $b) {
            if (strtolower((string)($b['status'] ?? '')) === 'active') {
                if (!empty($b['giver_name'])) $creditors[$b['giver_name']] = ($creditors[$b['giver_name']] ?? 0) + 1;
                if (!empty($b['receiver_name'])) $debtors[$b['receiver_name']] = ($debtors[$b['receiver_name']] ?? 0) + 1;
            }
        }
        arsort($creditors);
        arsort($debtors);
        $analysis['top_creditors'] = array_slice($creditors, 0, 10, true);
        $analysis['top_debtors'] = array_slice($debtors, 0, 10, true);
        $clanCounts = [];
        foreach ($boons as $b) {
            $gc = $b['creditor_clan'] ?? 'Unknown';
            $rc = $b['debtor_clan'] ?? 'Unknown';
            if (!isset($clanCounts[$gc])) $clanCounts[$gc] = ['given' => 0, 'received' => 0];
            if (!isset($clanCounts[$rc])) $clanCounts[$rc] = ['given' => 0, 'received' => 0];
            $clanCounts[$gc]['given']++;
            $clanCounts[$rc]['received']++;
        }
        $analysis['by_clan'] = $clanCounts;
        return ['success' => true, 'analysis' => $analysis];
    }

    public function voidBoonsOnDeath(string $characterName): array
    {
        $this->supabase();
        $chars = supabase_table_get('characters', ['select' => 'id', 'character_name' => 'eq.' . $characterName, 'limit' => '1']);
        if (empty($chars)) {
            return ['success' => false, 'message' => "Character '{$characterName}' not found", 'voided_count' => 0, 'boon_ids' => [], 'character' => $characterName, 'character_id' => null];
        }
        $characterId = (int)$chars[0]['id'];
        $rows = supabase_table_get('boons', ['select' => 'id,notes', 'debtor_id' => 'eq.' . $characterId, 'status' => 'eq.active']);
        $voidedIds = [];
        $note = '[Voided: Debtor deceased on ' . date('Y-m-d H:i:s') . ']';
        foreach ($rows as $r) {
            $existingNotes = trim((string)($r['notes'] ?? ''));
            $newNotes = $existingNotes !== '' ? $existingNotes . ' | ' . $note : $note;
            $res = supabase_rest_request('PATCH', '/rest/v1/boons', ['id' => 'eq.' . $r['id']], [
                'status' => 'cancelled',
                'notes' => $newNotes,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['Prefer: return=minimal']);
            if ($res['error'] === null) {
                $voidedIds[] = $r['id'];
            }
        }
        return [
            'success' => true,
            'voided_count' => count($voidedIds),
            'boon_ids' => $voidedIds,
            'character' => $characterName,
            'character_id' => $characterId,
            'message' => 'Voided ' . count($voidedIds) . ' boon(s) owed by deceased character ' . $characterName
        ];
    }
}
