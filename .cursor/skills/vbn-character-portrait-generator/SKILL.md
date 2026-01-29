---
name: vbn-character-portrait-generator
description: Generate a Valley by Night (World of Darkness cinematic realism) character portrait from a character JSON. Extracts appearance, morality, traits; builds noir-style prompt with humanity-driven subtle vampiric cues; outputs 1024×1024 image and sidecar txt. Use when the user provides character.json (or a character sheet JSON) and asks for a portrait, VbN portrait, or character image generation.
---

# VbN Character Portrait Generator

## Goal

Generate a Valley by Night (World of Darkness cinematic realism) character portrait from a provided character.json in context. Save the image to `images-generated/<slugified_character_name>_<YYYYMMDD-HHMMSS>.png` and a sidecar `.txt` with prompts and extracted highlights.

## Inputs

- A single character sheet file in context (e.g. `character.json` or any character JSON from the project).

## Outputs

1. One generated image: square 1:1, 1024×1024, saved to `images-generated/<slug>_<YYYYMMDD-HHMMSS>.png`.
2. Sidecar text file `images-generated/<same_filename>.txt` containing:
   - final_prompt_used
   - negative_prompt_used
   - extracted_character_highlights (bullet list)
   - morality_visualization_notes (bullet list)

## Process (execute in order)

### 1. Parse character.json

Extract (treat missing as empty and continue):

- character_name, clan, generation, sire, title, epithet, camarilla_status/sect
- appearance, appearance_detailed.short_summary, appearance_detailed.detailed_description
- biography, notes, equipment
- traits (Physical/Social/Mental), negativeTraits (Physical/Social/Mental)
- merits_flaws (type, category, description)
- disciplines (names, levels, powers if present)
- morality: path_name, humanity, path_rating, willpower, conscience/self_control/courage

### 2. Character highlights

Synthesize into:

- 1–2 sentence visual identity summary
- 6–10 concrete visual cues (wardrobe, grooming, posture, accessories, environment)
- 3–5 mood cues (tone words)
- 3–5 symbolic cues (optional; from biography/notes/merits_flaws)

Keep everything Masquerade-friendly; no overt magic, no gore.

### 3. Morality-driven visualization (required)

Use morality.humanity (or morality.path_rating if humanity missing) as 1–10 to influence subtle vampiric appearance. Default if missing: Humanity 7–8; note in morality_visualization_notes.

| Humanity | Visual treatment |
|----------|------------------|
| 9–10 | Nearly living: warm skin microtones, soft eye moisture, minimal pallor; open/empathetic expression; vampiric cues barely perceptible |
| 7–8 | Default VbN vamp: slightly cooler pallor, predatory catchlight, restrained stillness; composed, controlled, watchful |
| 5–6 | Cooler pallor, reduced warmth in lips/cheeks, faint under-eye shadow; less emotive, steady/unblinking gaze; faint “wrongness” in stillness |
| 3–4 | Porcelain-cool skin, deeper under-eye shadow, sharper planes; distant, calculating; subtle tell: tightened jaw, reduced blink, uncanny calm |
| 1–2 | Extreme cool pallor, statue-like stillness; emotionless, predatory focus; realistic only—no fangs, veins, gore, monstrous deformities unless JSON says so |

If path_name ≠ "Humanity", apply the same mapping but note in morality_visualization_notes that humanity look is used as visual proxy for distance-from-human norms.

### 4. Style lock (VbN cinematic)

- Square portrait 1:1, 1024×1024
- Cinematic neo-noir gothic; 1994 Arizona realism
- Medium-high contrast; directional/volumetric lighting; subtle haze/dust motes
- Low-to-medium saturation; warm/cool balance (teal shadows, amber highlights)
- Eyes and face sharp; background softly blurred (shallow DoF)

### 5. Time-of-day rule (required)

Default: All vampire portraits are nocturnal. Use **NIGHT** unless one of the following is true:

- character.json.notes explicitly mention daytime activity
- character.json.biography explicitly states the character can operate by day
- The user explicitly requests a daytime image

**If NIGHT:**

- Environment must read as nighttime or deep pre-dawn: **dark sky** (navy, black, or starfield), **no sun visible anywhere**, no warm daylight tones in sky or background.
- Lighting must be artificial (streetlights, moonlight, neon, firelight) or moonlit desert only.
- **Mandatory in final_prompt_used:**
  1. **Open** the prompt with this block (so the model sees it first):  
     `Night scene only. Dark sky, no sun, no sunlight. Lit only by moonlight, streetlights, neon, or firelight. The whole image must look like night—dark blues and blacks, no warm daylight in sky or background.`
  2. In the **environment/lighting** description use explicit cues: dark night sky, moonlit, starfield or overcast night, no sun in frame.
  3. **Close** the prompt with:  
     `Time of day: Night. No daylight, no sunrise, no sunset, no golden hour. Moonlit or artificial lighting only.`
- Add to **negative_prompt_used** (all of these when NIGHT):  
  `daytime, daylight, day, sun, sunlight, sunny, sunlit, sunlit sky, golden hour, sunrise, sunset, dawn, dusk, noon, afternoon, morning, bright blue sky, blue sky, bright sky, clouds in daylight, natural daylight, outdoor daylight, warm daylight, daylight portrait`

**If DAY is allowed:**

- Include a note in **morality_visualization_notes** explaining why daylight is permitted.
- Maintain Masquerade realism (shade, overcast, interiors, or indirect light preferred).

### 6. Prompt construction

Build a single **final_prompt_used** in this order:

- **When NIGHT**: Open with the mandatory night-scene block (see Time-of-day rule). Do not put subject or “cinematic” before it—night first.
- **Subject block**: name, clan/role, age impression, ethnicity if stated, wardrobe/grooming, posture
- **Vampiric cues**: tuned by humanity mapping (subtle only)
- **Lighting block** with HEX accents (for NIGHT use only cool/moonlit tones; no warm “sun” amber in sky):
  - Noir blue-black #0D0E10 (shadows, night sky)
  - Desert amber #C87B3E (key highlights—from moonlight or artificial light, not sun)
  - Muted gold #B89B64 (secondary highlights)
  - Deep crimson #7A1E1E (very subtle undertone)
  (If JSON suggests a different palette, incorporate but keep noir and **night**.)
- **Environment block**: Must include **dark night sky** or **moonlit** or **starfield** or **overcast night**; Phoenix/Mesa/East Valley cues from biography/notes; do not invent landmarks by name unless present.
- **Camera**: film-still realism, realistic lens, subtle bloom, shallow depth of field
- **Stamp**: "Phoenix, Arizona, 1994"
- **Final line**: "Masquerade-friendly subtlety; no overt supernatural display."
- **When NIGHT**: End with the hard constraint line verbatim (see Time-of-day rule).

### 7. Negative prompt (required)

**negative_prompt_used** must include:

- cartoon, anime, illustration, painterly brush strokes, CGI, video game render
- glam beauty retouch, plastic skin, over-sharpening, HDR halos
- overt fangs, gore, blood splatter, horror monster, deformed face (unless JSON requires)
- fantasy architecture, medieval clothing, modern 2020s streetwear
- text overlays, captions, watermarks, logos, date stamps
- When NIGHT (default): daytime, daylight, day, sun, sunlight, sunny, sunlit, sunlit sky, golden hour, sunrise, sunset, dawn, dusk, noon, afternoon, morning, bright blue sky, blue sky, bright sky, clouds in daylight, natural daylight, outdoor daylight, warm daylight, daylight portrait

### 8. Image generation and saving

- Generate exactly one image from final_prompt_used + negative_prompt_used (1024×1024, square).
- Save to `images-generated/<slugified_character_name>_<YYYYMMDD-HHMMSS>.png`.
- Slugify: lowercase, spaces → hyphens, remove non-alphanumerics except hyphen.
- Save sidecar `.txt` with: final_prompt_used, negative_prompt_used, extracted_character_highlights, morality_visualization_notes.

### 9. Failure rules

- If character_name missing, use "unknown-character" in filename.
- If morality data missing, default to Humanity 7–8 and note in morality_visualization_notes.
- Do not ask the user questions; proceed with best-effort from available JSON.
