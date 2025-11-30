# PART IX — NAMING CONVENTIONS & FOLDER STRUCTURE  
## (Full, Exhaustive Version)

## 1. Overview
This system unifies naming, versioning, and folder structure across all assets in the Valley by Night project:
- JSON records  
- images  
- UI resources  
- cinematic files  
- 3D assets  
- location art  
- floorplans  
- storyboards  
- marketing materials  
- website code  

This ensures:
- Consistent imports into Cursor AI  
- Predictable database automation  
- Clean version control  
- Proper organization for web deployment  
- Cohesive asset pipeline for developers, artists, and storytellers  

---

# 2. Universal Naming Rules

## 2.1 Style
- **snake_case** only  
- **lowercase**  
- **no spaces**  
- **no special characters** except `_` and `-`  
- **no uppercase**  
- **no camelCase**  

## 2.2 Versioning
Use:  
```
_v01, _v02, _v03
```
Never use:  
```
final, real_final, v1done, fixed
```

## 2.3 Extensions
- JSON: `.json`  
- Markdown: `.md`  
- Images: `.png`, `.webp`  
- Web code: `.php`, `.css`, `.js`  
- 3D: `.fbx`, `.obj`, `.blend`  

---

# 3. JSON Naming Conventions

## 3.1 Characters
```
character_[first_last].json
```
Example:
```
character_lilith_nightshade.json
```

## 3.2 Rumors
```
rumor_[short_description].json
```

## 3.3 Locations
```
location_[place_name].json
```

## 3.4 Havens
```
haven_[character_or_group].json
```

## 3.5 Agents
```
agent_[name].json
```

---

# 4. Image Naming Conventions

## 4.1 Portraits (1024×1024)
```
char_[first_last]_portrait_v##_1024.png
```

## 4.2 UI Icons (512×512)
```
char_[first_last]_icon_512.png
```

## 4.3 Clan Glyphs
```
clan_[clan_name]_gold_512.png
```

## 4.4 Location Tiles
```
location_[name]_tile_1024.png
```

## 4.5 Cinematic Panels
```
cin_[scene_name]_panel_##.png
```

## 4.6 Storyboard Panels
```
sb_[scene_name]_panel_##.png
```

## 4.7 Floorplans
```
map_[location_name]_floorplan_v##.png
```

## 4.8 Marketing Art
```
mkt_[type]_[name]_1920x1080_v##.png
```

## 4.9 3D Texture Maps
```
[asset]_[material]_[maptype]_[resolution].png
```
Where `maptype` is:
```
basecolor, roughness, metallic, normal, ao, opacity, emission
```

---

# 5. Cinematic Folder Structure

## 5.1 Root
```
/cinematics/[scene_name]/
```

## 5.2 Inside each folder:
```
scene_[name]_script.md
scene_[name]_storyboards/
scene_[name]_panels/
scene_[name]_animatic.mp4
scene_[name]_final_render/
scene_[name]_audio/
```

## 5.3 Storyboard + Animatic
```
sb_[scene_name]_panel_##.png
animatic_[scene_name]_v##.mp4
```

---

# 6. UI Folder Structure

```
/ui/
   /portraits/
   /icons/
   /clan_logos/
   /location_tiles/
   /banners/
   /modal_backgrounds/
   /cinematic_panels/
   /css/
```

CSS filenames must be exact:
```
global.css
bootstrap-overrides.css
dashboard.css
modal.css
character_view.css
login.css
```

---

# 7. Maps & Floorplans

```
/maps/
   /floorplans/
   /tiles/
   /overworld/
```

Floorplan naming:
```
map_[location]_floorplan_v##.png
map_[location]_blueprint_v##.png
map_[location]_parchment_v##.png
```

---

# 8. 3D Assets

## 8.1 Directory
```
/3d/
   /characters/
   /props/
   /environments/
   /vehicles/
   /textures/
   /exports/
```

## 8.2 Character Meshes
```
char_[first_last]_mesh_v##.fbx
```

## 8.3 Props
```
prop_[name]_mesh_v##.fbx
```

## 8.4 Vehicles
```
vehicle_[make_model]_v##.fbx
```

## 8.5 Textures
```
char_[name]_[part]_[maptype]_2k.png
prop_[name]_[maptype]_2k.png
env_[location]_[asset]_[maptype]_2k.png
```

---

# 9. Website / Admin Panel Structure

## 9.1 Root
```
/website/
   /admin/
      /css/
      /js/
      /images/
      /php/
   /public/
      /css/
      /js/
      /images/
```

Admin panel pages:
```
admin_[function].php
```

---

# 10. Character Asset Folder Structure

```
/characters/[first_last]/
   /portrait/
   /bio/
   /json/
   /3d/
   /ui/
   /cinematic/
```

Example:
```
/characters/lilith_nightshade/
   portrait/char_lilith_nightshade_portrait_v02_1024.png
   json/character_lilith_nightshade.json
   ui/lilith_banner_1920.png
   cinematic/lilith_intro/
```

---

# 11. Agents Folder Structure

```
/agents/
   /Character_Agent/
   /Rumor_Agent/
   /Influence_Agent/
   /Boon_Agent/
   /Camarilla_Positions/
   /Laws_of_the_Night/
```

Each agent includes:
```
agent_[name].md
rules.md
templates.md
examples/
scripts/
```

---

# 12. Zipped Deliverable Structure

When exporting full packages:
```
VbN_ArtSystem_v##.zip
   /01_portraits/
   /02_cinematics/
   /03_locations/
   /04_3d/
   /05_ui/
   /06_storyboards/
   /07_marketing/
   /08_floorplans/
   /09_structure/
```

---

# 13. Changelog System

Each major file may include:
```
CHANGELOG_[file].md
```
with:
- version  
- edits  
- author  
- rationale  

---


---
