# Misfortune — 3D Character Generation Prompt

## Metadata
- Character ID: `79a7ead4-ecd0-453a-a01c-10e4707bd3fa`
- Source: Supabase MCP (`public.characters` + related tables)
- Template: Fixed Style Agent 3D prompt template (Blender text-to-3D)

### System
Blender text-to-3D character pipeline prompt, optimized for a stylized-realistic CC5-to-Blender cinematic workflow.

### Identity
- Character Name: Misfortune
- Clan/Faction: Malkavian
- Concept: Boon Collector
- Nature / Demeanor: Visionary / Trickster
- Sect Alignment: Independent
- Generation: 9
- Morality Context: Humanity

### Art Bible Constraints (mandatory)
- Match Valley by Night aesthetic: Gothic-Noir, Phoenix 1994, low-saturation cinematic realism.
- Prioritize silhouette readability first, texture detail second.
- Semi-realistic stylization only; no cartoon exaggeration, no hyper-gloss realism.
- Materials must stay matte-to-semi-matte, with desert dust, edge wear, and period-authentic aging.
- Pose and expression must communicate restrained tension, not superhero theatrics.
- Preserve continuity with portrait system and cinematic system language.

### Character Visual Direction
Build a full-body 3D character in neutral A-pose, proportionally realistic, with subtle cinematic enhancement only.
Design language should communicate: Boon Collector. Keep the visual psychology anchored in Visionary (inner driver) and Trickster (public mask).
Appearance foundation: Misfortune presents a carefully managed visual identity that supports the role of boon collector in Phoenix nights. As a Malkavian aligned with Independent politics, their look is built to communicate competence before a word is spoken. Witnesses first notice that In Elysium he wears a mismatched black and burgundy suit with subtle bells and faint theatrical makeup; in his haven he dons a full jester costume surrounded by pinned IOUs and candles, and that first impression tends to hold under pressure. Their Nature reads as Visionary, while their Demeanor performs as Trickster, producing subtle contradictions that make them difficult to profile. Traits such as Charismatic, Charming, Cold Rationalist, Cryptic, Cunning, Deceptively Agile show in stance, grooming, and eye contact, turning presentation into tactical advantage rather than vanity. Disciplines including Auspex 5, Dominate 5, Obfuscate 3 reinforce the effect: attention shifts where they want it, tension rises or drops on cue, and witnesses remember exactly what they were meant to remember. With Humanity at 5, appearance is also armor against the Beast; they cultivate ritualized habits that keep predation from becoming carel

### Proportions and Silhouette
- Anatomically plausible proportions, readable at medium distance.
- Distinct silhouette massing around head/shoulder/torso profile to make this character identifiable in low-key lighting.
- Keep accessory count controlled; avoid noisy detail stacks that collapse silhouette readability.
- Build a secondary silhouette identifier tied to role (coat shape, collar line, posture vector, or profile-defining accessory).

### Materials, Textures, and Surface Treatment
- Material style: period-accurate 1994 wardrobe, worn realism, low-gloss materials, silhouette-first readability
- Skin shader: subtle pore detail, low specularity, controlled subsurface, no plastic sheen.
- Clothing texture: layered realism (macro fading, mid scuffs, micro grain), no sterile cloth surfaces.
- Metal/leather details: worn edges, muted reflections, period-authentic roughness response.
- Ensure all PBR maps are coherent for BaseColor, Roughness, Metallic, Normal, AO; add Emission only when lore-justified.

### Face, Expression, and Body Language
- Default expression: emotionally restrained, predatory calm, narrative tension under control.
- Idle body language: grounded stance, balanced center of gravity, intentional stillness.
- Expression detail should imply lived history, not theatrical performance.
- Keep face readable in close-up with noir key + rim setup.

### Discipline and Supernatural Cues (subtle, baked into design)
Primary powers: Auspex 5, Dominate 5, Obfuscate 3
Traits: Cold Rationalist, Cunning, Distracted by patterns, Obsessive, Patient, Pattern-Oriented Thinker, Perceptive, Strategic, Deceptively Agile, Frail, Graceful, Light-footed
Abilities: Hermetic Lore, Investigation, Occult, Ritual Theory, Stealth, Alertness, Empathy, Etiquette, Expression, Leadership, Performance, Streetwise
Use subtle embedded cues only (surface motifs, scar geometry, jewelry symbolism, posture cues, eye micro-accents, garment ritual marks).
Do not produce overt VFX layers in base mesh; cues must survive as believable 1994 physical design elements.

### Rigging / Animation / Technical Targets
- Deliver clean topology suitable for deformation in face, shoulders, elbows, hands, hips.
- Keep A-pose neutral for retargeting.
- Support SubD0 working / SubD2 render strategy.
- Texture target: 4K source authoring, downscale-ready to 2K for runtime variants.
- LOD guidance: LOD0 hero quality, with topology that can cleanly derive LOD1 and LOD2.
- Ensure hands and face hold up in close cinematic framing.

### Integration Requirements
- Must sit naturally inside Valley by Night environments (desert urban interiors/exteriors, noir lighting, practical 1994 props).
- Ensure wardrobe and surface wear match environment and social role.
- Character must read as part of same world as existing portraits and cinematic stills.

### Render Intent
Output should look production-ready for Blender cinematic scenes: controlled highlights, deep but readable shadow structure, and period-faithful palette discipline.

### Negative Prompt Block (mandatory)
(no neon, no cyberpunk, no modern 2020s tech, no anime proportions, no cartoon shading, no plastic skin, no glossy latex look, no superhero stance, no comedic stylization, no overexposed white lighting, no oversaturation, no floating fantasy VFX, no sci-fi armor, no clean showroom finish, no Instagram beauty grading)


---

### 3D Prompt (Short ~250 words)
Create a full-body 3D character for Blender text-to-3D, in a semi-realistic Gothic-Noir style consistent with Valley by Night (Phoenix 1994). Character: Misfortune. Clan/faction: Malkavian. Core concept: Boon Collector. Psychological axis: Visionary (inner driver) + Trickster (public mask).

Use anatomically plausible proportions and a clean, readable silhouette first. Keep the model production-ready for CC5-to-Blender cinematic workflow: neutral A-pose, deformation-safe topology, and material assignments suitable for PBR (BaseColor, Roughness, Metallic, Normal, AO). Skin must be natural and low-gloss, with controlled subsurface and no plastic sheen.

Appearance direction: Misfortune presents a carefully managed visual identity that supports the role of boon collector in Phoenix nights. As a Malkavian aligned with Independent politics, their look is built to communicate competence before a word is spoken. Witnesses first notice that In Elysium he wears a mismatched black and burgundy suit with subtle bells and faint theatrical makeup; in his haven he dons a full jester costume surrounded by pinned IOUs and candles, and that first impression tends to hold under pressure. Their Nature reads as Visionary, while thei

Wardrobe and surface treatment must feel period-authentic (1994), slightly worn, dust-aware, and integrated with noir desert urban environments. Keep details intentional, not noisy. Embed supernatural identity subtly through costume motifs, micro-surface cues, posture language, and restrained facial energy; avoid overt magical VFX in base mesh.

Discipline cues to encode subtly: Auspex 5, Dominate 5, Obfuscate 3.

Lighting-readiness requirement: character must remain readable under deep shadow with controlled key/rim lighting and low saturation.

### Negative Prompt Block
(no neon, no cyberpunk, no modern 2020s tech, no cartoon proportions, no anime stylization, no glossy plastic skin, no superhero poses, no comedic tone, no oversaturated palette, no sci-fi armor, no floating magical VFX)

