# PART IV — 3D ASSET SYSTEM  
## (Full, Exhaustive Version)

## 1. Overview
The 3D Asset System governs every element of Valley by Night’s three‑dimensional production pipeline:
- Characters (CC5 → Blender → Cinematics)
- Props (weapons, tools, objects)
- Environments (modular structures)
- Vehicles
- Texture maps (PBR)
- Shading and rendering workflows
- Era accuracy (Phoenix, 1994)
- Visual cohesion with 2D portrait and cinematic styles

All 3D assets must align with:
- Gothic–Noir tone  
- Phoenix 1994 realism  
- Cinematic lighting  
- Location guides  
- UI integration  
- Final rendering pipeline for Blender cinematic scenes  

This section is exhaustive and covers every requirement.

---

# 2. Asset Categories

## 2.1 Characters
- Produced in **Character Creator 5**
- Exported with **4K texture sets**
- Downscaled to **2K** for in-game/UI usage
- Rigged automatically by CC5
- Optimized in Blender for animation & rendering

## 2.2 Props
Includes:
- weapons  
- ritual tools  
- personal items  
- furniture  
- documents  
- analog electronics  

Props must reflect:
- 1994 authenticity  
- subtle gothic influence  
- light desert wear and dust  

## 2.3 Vehicles
Era-accurate vehicles only:
- 80s/90s sedans  
- pickup trucks  
- muscle cars  
- police cruisers from 1990–1994  
- NO modern vehicles  

## 2.4 Environment Assets
Includes:
- modular walls/floors  
- doors/windows  
- signage  
- clutter props  
- lighting fixtures  
- exterior kits  
- desert terrain meshes  

Environment assets must integrate perfectly with Location System (Part III).

---

# 3. Technical Standards

## 3.1 Resolution
- All 3D textures: **2048 × 2048** standard  
- 1024 × 1024 allowed for small props  
- 4096 × 4096 for hero characters or cinema close-ups  

## 3.2 PBR Workflow
Each asset requires:
- BaseColor
- Roughness
- Metallic
- Normal  
- Ambient Occlusion  
- Emission (if needed)
- Opacity (if needed)

Workflow standard:
- Metallic/Roughness PBR  
- No spec/gloss unless conversion required  

## 3.3 File Formats
- Models: FBX, OBJ  
- Textures: PNG (preferred), WEBP for UI, EXR optional  
- Blender scenes: BLEND  

---

# 4. Character Workflow (CC5 → Blender)

## 4.1 Export Rules
From Character Creator:
- Use **4K** textures initially  
- Include A‑Pose or Neutral  
- Merge materials when appropriate  
- Export FBX for Blender  

## 4.2 Blender Setup
Upon import:
- Apply **SubD 0** during animation work  
- Set **SubD 2** ONLY at render  
- Review crease edges  
- Clean unused vertex groups  
- Confirm texture linking  

## 4.3 Skin Shading
Skin must adhere to:
- medium pore detail  
- zero plastic shine  
- slightly desaturated highlights  
- natural undertones  
- noir film influence  

Subsurface settings:
- low radius  
- soft scattering  
- maintain shadow detail  

## 4.4 Hair Shading
Hair must:
- have natural roughness  
- some dryness from desert climate  
- avoid lacquered shine  

---

# 5. Clothing Rules

## 5.1 Era Accuracy
Use only fabrics common in 1994:
- denim  
- leather  
- cotton weave  
- polyester  
- velvet (Toreador/Setite)  
- wool blends  

## 5.2 Wear & Aging
Clothing must show:
- slight wear at edges  
- minor discoloration  
- dust buildup  
- stitching imperfections  

## 5.3 Clan-Specific Rules
- **Toreador:** satin, velvet, elegant folds  
- **Brujah:** worn leather, heavy denim  
- **Gangrel:** rugged outdoor wear  
- **Nosferatu:** industrial rags, patchwork  
- **Ventrue:** clean business attire  
- **Giovanni:** old‑world formal wear  
- **Setite:** exotic and sensual materials  

---

# 6. Props & Weapons

## 6.1 General Prop Philosophy
Props extend character personality:
- Nothing brand-new  
- Everything slightly worn  
- Dust, minor scratches, fingerprints  

## 6.2 Weapons
Realistic 1990s-era:
- knives  
- revolvers  
- early 90s pistols  
- bats/pipes  
- ritual knives  
- brass implements  

NO:
- futuristic weapons  
- tactical modern gear  
- neon effects  

## 6.3 Ritual Tools
Ritual objects for Giovanni, Setites:
- engraved brass  
- old silver  
- patina  
- wax residue  
- faint stains  

---

# 7. Vehicles

Vehicles must:
- reflect Phoenix 1994 environment  
- appear dusty, sun-bleached  
- contain believable wear  

Lighting compatibility:
- noir rim lights  
- soft highlights  
- no chrome glare  

---

# 8. Environment Assets

## 8.1 Materials
Desert-specific:
- stucco  
- faded paint  
- dusty tiles  
- concrete  
- cracked asphalt  
- chain-link fencing  
- neon tubes  

## 8.2 Interior Assets
Based on faction:
- gothic marble (Elysium)  
- industrial metal (Nosferatu)  
- tile/grime (Hospital)  
- velvet & gold (Setites)  
- desert motel wood & fabrics (Gangrel)  

---

# 9. Lighting & Shading Rules

## 9.1 Global Shader Philosophy
All shaders:
- emphasize roughness  
- avoid high gloss  
- incorporate filmic fresnel  
- use subtle grain  
- match noir palette  

## 9.2 Rim Light Rules
- Subtle, not overbearing  
- Warm for interior  
- Cool for exterior  

## 9.3 Metal
- subdued reflections  
- muted saturation  
- worn edges  

---

# 10. Optimization

## 10.1 Polycount Targets
- hero characters: **50–80k**  
- NPC characters: **20–40k**  
- small props: **300–5k**  
- vehicles: **10–25k**  

## 10.2 LOD System
Game assets require:
- LOD0  
- LOD1 (50%)  
- LOD2 (20%)  

## 10.3 UV Rules
- No overlapping except mirrored symmetrical regions  
- Texel density consistency required  

---

# 11. Naming Conventions
Summaries follow Part IX.

---

# 12. Prompt Templates
Detailed versions stored in 04b.

---

# 13. Render Pipeline
1. Import CC5 asset  
2. Setup materials  
3. Pose/animate  
4. Apply SubD at render  
5. Cinematic lighting  
6. Composite (grain, bloom, vignette)  
7. Export sequence  

---


---
