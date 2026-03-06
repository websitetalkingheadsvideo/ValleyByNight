# Mary 'Rain' Miller — 3D Character Generation Prompt

## Metadata
- Character ID: `3f0e41f3-f304-437f-935c-c1373ef32bd9`
- Source: Supabase MCP (`public.characters` + related tables)
- Template: Fixed Style Agent 3D prompt template (Blender text-to-3D)

### System
Blender text-to-3D character pipeline prompt, optimized for a stylized-realistic CC5-to-Blender cinematic workflow.

### Identity
- Character Name: Mary 'Rain' Miller
- Clan/Faction: Tremere
- Concept: Geological Forensic Apprentice
- Nature / Demeanor: Caregiver / Child
- Sect Alignment: Independent
- Generation: 13
- Morality Context: not specified

### Art Bible Constraints (mandatory)
- Match Valley by Night aesthetic: Gothic-Noir, Phoenix 1994, low-saturation cinematic realism.
- Prioritize silhouette readability first, texture detail second.
- Semi-realistic stylization only; no cartoon exaggeration, no hyper-gloss realism.
- Materials must stay matte-to-semi-matte, with desert dust, edge wear, and period-authentic aging.
- Pose and expression must communicate restrained tension, not superhero theatrics.
- Preserve continuity with portrait system and cinematic system language.

### Character Visual Direction
Build a full-body 3D character in neutral A-pose, proportionally realistic, with subtle cinematic enhancement only.
Design language should communicate: Geological Forensic Apprentice. Keep the visual psychology anchored in Caregiver (inner driver) and Child (public mask).
Appearance foundation: Mary 'Rain' Miller presents a carefully managed visual identity that supports the role of geological forensic apprentice in Phoenix nights. As a Tremere aligned with Independent politics, their look is built to communicate competence before a word is spoken. There is no old appearance entry, but repeated sightings describe a silhouette designed for control, recognition, and deniability at the same time. Their Nature reads as Caregiver, while their Demeanor performs as Child, producing subtle contradictions that make them difficult to profile. Their posture, grooming, and timing imply trained situational awareness, with no wasted movement and no accidental tells. Disciplines including Auspex 2, Dominate 2, Thaumaturgy 2 reinforce the effect: attention shifts where they want it, tension rises or drops on cue, and witnesses remember exactly what they were meant to remember. Their moral center is less explicit in the record, but their styling still reads as controlled, purposeful, and anchored in long-term survival. Taken together, the result is memorable without being loud: a persona built for negotiation, intimidation, and plausible innocence in equal measure.

### Proportions and Silhouette
- Anatomically plausible proportions, readable at medium distance.
- Distinct silhouette massing around head/shoulder/torso profile to make this character identifiable in low-key lighting.
- Keep accessory count controlled; avoid noisy detail stacks that collapse silhouette readability.
- Build a secondary silhouette identifier tied to role (coat shape, collar line, posture vector, or profile-defining accessory).

### Materials, Textures, and Surface Treatment
- Material style: scholarly tailoring, ritual accessories, archival fabrics, precise line work
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
Primary powers: Auspex 2, Dominate 2, Thaumaturgy 2
Traits: none listed
Abilities: Hermetic Lore, Occult, Ritual Theory, Etiquette, Expression, Performance
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
Create a full-body 3D character for Blender text-to-3D, in a semi-realistic Gothic-Noir style consistent with Valley by Night (Phoenix 1994). Character: Mary 'Rain' Miller. Clan/faction: Tremere. Core concept: Geological Forensic Apprentice. Psychological axis: Caregiver (inner driver) + Child (public mask).

Use anatomically plausible proportions and a clean, readable silhouette first. Keep the model production-ready for CC5-to-Blender cinematic workflow: neutral A-pose, deformation-safe topology, and material assignments suitable for PBR (BaseColor, Roughness, Metallic, Normal, AO). Skin must be natural and low-gloss, with controlled subsurface and no plastic sheen.

Appearance direction: Mary 'Rain' Miller presents a carefully managed visual identity that supports the role of geological forensic apprentice in Phoenix nights. As a Tremere aligned with Independent politics, their look is built to communicate competence before a word is spoken. There is no old appearance entry, but repeated sightings describe a silhouette designed for control, recognition, and deniability at the same time. Their Nature reads as Caregiver, while their Demeanor performs as Child, producing subtle contradictions that make them difficult to profile. T

Wardrobe and surface treatment must feel period-authentic (1994), slightly worn, dust-aware, and integrated with noir desert urban environments. Keep details intentional, not noisy. Embed supernatural identity subtly through costume motifs, micro-surface cues, posture language, and restrained facial energy; avoid overt magical VFX in base mesh.

Discipline cues to encode subtly: Auspex 2, Dominate 2, Thaumaturgy 2.

Lighting-readiness requirement: character must remain readable under deep shadow with controlled key/rim lighting and low saturation.

### Negative Prompt Block
(no neon, no cyberpunk, no modern 2020s tech, no cartoon proportions, no anime stylization, no glossy plastic skin, no superhero poses, no comedic tone, no oversaturated palette, no sci-fi armor, no floating magical VFX)

