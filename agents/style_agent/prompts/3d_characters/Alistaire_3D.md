# Alistaire — 3D Character Generation Prompt

## Metadata
- Character ID: `4f61c99e-4d4a-4257-9b29-7fe89f6dd013`
- Source: Supabase MCP (`public.characters` + related tables)
- Template: Fixed Style Agent 3D prompt template (Blender text-to-3D)

### System
Blender text-to-3D character pipeline prompt, optimized for a stylized-realistic CC5-to-Blender cinematic workflow.

### Identity
- Character Name: Alistaire
- Clan/Faction: Nosferatu
- Concept: Subterranean Information Broker
- Nature / Demeanor: Survivor / Judge
- Sect Alignment: Independent
- Generation: 13
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
Design language should communicate: Subterranean Information Broker. Keep the visual psychology anchored in Survivor (inner driver) and Judge (public mask).
Appearance foundation: Alistaire presents a carefully managed visual identity that supports the role of subterranean information broker in Phoenix nights. As a Nosferatu aligned with Independent politics, their look is built to communicate competence before a word is spoken. Witnesses first notice that Gaunt and elongated features, moves with a preternatural stillness, and that first impression tends to hold under pressure. Their Nature reads as Survivor, while their Demeanor performs as Judge, producing subtle contradictions that make them difficult to profile. Traits such as Commanding, Cunning, Disciplined, Enduring, Intimidating, Observant show in stance, grooming, and eye contact, turning presentation into tactical advantage rather than vanity. Disciplines including Animalism 5, Obfuscate 4, Potence 2 reinforce the effect: attention shifts where they want it, tension rises or drops on cue, and witnesses remember exactly what they were meant to remember. With Humanity at 5, appearance is also armor against the Beast; they cultivate ritualized habits that keep predation from becoming carelessness. Taken together, the result is memorable without being loud: a persona built for negotiation, intimidation

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
Primary powers: Animalism 5, Obfuscate 4, Potence 2
Traits: Disciplined, Observant, Patient, Strategic, Enduring, Repugnant (Nosferatu Curse), Resilient, Steady, Commanding, Cunning, Intimidating
Abilities: Academics, Awareness, Computer, Investigation, Occult, Politics, Security, Technology, Animal Ken, Stealth, Alertness, Empathy
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
Create a full-body 3D character for Blender text-to-3D, in a semi-realistic Gothic-Noir style consistent with Valley by Night (Phoenix 1994). Character: Alistaire. Clan/faction: Nosferatu. Core concept: Subterranean Information Broker. Psychological axis: Survivor (inner driver) + Judge (public mask).

Use anatomically plausible proportions and a clean, readable silhouette first. Keep the model production-ready for CC5-to-Blender cinematic workflow: neutral A-pose, deformation-safe topology, and material assignments suitable for PBR (BaseColor, Roughness, Metallic, Normal, AO). Skin must be natural and low-gloss, with controlled subsurface and no plastic sheen.

Appearance direction: Alistaire presents a carefully managed visual identity that supports the role of subterranean information broker in Phoenix nights. As a Nosferatu aligned with Independent politics, their look is built to communicate competence before a word is spoken. Witnesses first notice that Gaunt and elongated features, moves with a preternatural stillness, and that first impression tends to hold under pressure. Their Nature reads as Survivor, while their Demeanor performs as Judge, producing subtle contradictions that make them difficult to profile. Trai

Wardrobe and surface treatment must feel period-authentic (1994), slightly worn, dust-aware, and integrated with noir desert urban environments. Keep details intentional, not noisy. Embed supernatural identity subtly through costume motifs, micro-surface cues, posture language, and restrained facial energy; avoid overt magical VFX in base mesh.

Discipline cues to encode subtly: Animalism 5, Obfuscate 4, Potence 2.

Lighting-readiness requirement: character must remain readable under deep shadow with controlled key/rim lighting and low saturation.

### Negative Prompt Block
(no neon, no cyberpunk, no modern 2020s tech, no cartoon proportions, no anime stylization, no glossy plastic skin, no superhero poses, no comedic tone, no oversaturated palette, no sci-fi armor, no floating magical VFX)

