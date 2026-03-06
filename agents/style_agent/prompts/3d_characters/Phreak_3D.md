# Phreak — 3D Character Generation Prompt

## Metadata
- Character ID: `740c7977-0c75-4065-95cf-69a8d744dd5f`
- Source: Supabase MCP (`public.characters` + related tables)
- Template: Fixed Style Agent 3D prompt template (Blender text-to-3D)

### System
Blender text-to-3D character pipeline prompt, optimized for a stylized-realistic CC5-to-Blender cinematic workflow.

### Identity
- Character Name: Phreak
- Clan/Faction: Nosferatu
- Concept: Elite hacker and digital resistance operative
- Nature / Demeanor: Rebel / Jester
- Sect Alignment: Independent
- Generation: 0
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
Design language should communicate: Elite hacker and digital resistance operative. Keep the visual psychology anchored in Rebel (inner driver) and Jester (public mask).
Appearance foundation: Phreak moves through the digital underground like a ghost in the machine made flesh, his presence a contradiction between the virtual world he commands and the physical form that anchors him to reality. As a Nosferatu, his curse has marked him in ways that make mortals look away and Kindred stare in fascinated horror—but unlike many of his clan, there's something almost cybernetic about his disfigurement, as if the curse recognized his connection to technology and made it literal. His build is slight, almost wiry, the kind of frame that suggests someone who spends more time hunched over keyboards than in physical activity, and his movements are quick and precise, like someone who's learned to optimize every motion for efficiency. His face is where the curse shows most clearly—features that might have been handsome twisted into something that makes people uncomfortable, skin that seems to shift and glitch like a corrupted image file, and eyes that hold the kind of intensity that comes from staring at screens for hours on end. His hair, what isn't affected by the curse, is kept practical—short and messy, as if he cuts it himself and doesn't care about the result. He favors clothing t

### Proportions and Silhouette
- Anatomically plausible proportions, readable at medium distance.
- Distinct silhouette massing around head/shoulder/torso profile to make this character identifiable in low-key lighting.
- Keep accessory count controlled; avoid noisy detail stacks that collapse silhouette readability.
- Build a secondary silhouette identifier tied to role (coat shape, collar line, posture vector, or profile-defining accessory).

### Materials, Textures, and Surface Treatment
- Material style: patched industrial fabrics, distressed coats, utility straps, grime-aware texture breakup
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
Primary powers: Animalism 2, Obfuscate 2, Potence 2
Traits: Alert, Attentive, Calm, Creative, Dedicated, Forgetful, Athletic, Clumsy, Energetic, Ferocious, Alluring, Bestial
Abilities: Computer, Electronics, Encryption, Investigation, Technology, Stealth, Alertness, Empathy, Etiquette, Expression, Intimidation, Leadership
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
Create a full-body 3D character for Blender text-to-3D, in a semi-realistic Gothic-Noir style consistent with Valley by Night (Phoenix 1994). Character: Phreak. Clan/faction: Nosferatu. Core concept: Elite hacker and digital resistance operative. Psychological axis: Rebel (inner driver) + Jester (public mask).

Use anatomically plausible proportions and a clean, readable silhouette first. Keep the model production-ready for CC5-to-Blender cinematic workflow: neutral A-pose, deformation-safe topology, and material assignments suitable for PBR (BaseColor, Roughness, Metallic, Normal, AO). Skin must be natural and low-gloss, with controlled subsurface and no plastic sheen.

Appearance direction: Phreak moves through the digital underground like a ghost in the machine made flesh, his presence a contradiction between the virtual world he commands and the physical form that anchors him to reality. As a Nosferatu, his curse has marked him in ways that make mortals look away and Kindred stare in fascinated horror—but unlike many of his clan, there's something almost cybernetic about his disfigurement, as if the curse recognized his connection to technology and made it literal. His build is slight, almost wiry, the kind of frame that suggest

Wardrobe and surface treatment must feel period-authentic (1994), slightly worn, dust-aware, and integrated with noir desert urban environments. Keep details intentional, not noisy. Embed supernatural identity subtly through costume motifs, micro-surface cues, posture language, and restrained facial energy; avoid overt magical VFX in base mesh.

Discipline cues to encode subtly: Animalism 2, Obfuscate 2, Potence 2.

Lighting-readiness requirement: character must remain readable under deep shadow with controlled key/rim lighting and low saturation.

### Negative Prompt Block
(no neon, no cyberpunk, no modern 2020s tech, no cartoon proportions, no anime stylization, no glossy plastic skin, no superhero poses, no comedic tone, no oversaturated palette, no sci-fi armor, no floating magical VFX)

