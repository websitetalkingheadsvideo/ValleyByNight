# Primogen Assignments Audit

## Currently Assigned Primogen
✅ **Misfortune** - Malkavian Primogen (Assignment ID: 5)
✅ **Étienne Duvalier** - Toreador Primogen (Assignment ID: 6)

## Characters Mentioned as Primogen (Need Assignment)

### 1. **Alistaire** - Nosferatu Primogen of Phoenix
- **File**: `reference/Characters/alistaire.json`
- **Title**: "Nosferatu Primogen of Phoenix"
- **Status**: Needs assignment
- **Character ID**: Unknown (needs to be checked in database)
- **Notes**: Session notes confirm this is the Nosferatu Primogen

### 2. **Butch Reed** - Brujah Primogen
- **File**: `reference/Characters/Butch Reed.json`
- **Concept**: "Gentle giant mechanic / Brujah Primogen"
- **Status**: Needs assignment
- **Character ID**: Unknown (needs to be checked in database)
- **Notes**: Brujah Primogen position exists in database (ID: `primogen_brujah`)

### 3. **Lilith Nightshade** - Former Malkavian Primogen
- **File**: `reference/Characters/lilith_nightshade.json`
- **Title**: "Malkavian Primogen of Phoenix"
- **Status**: **HISTORICAL/FORMER** - Should NOT be assigned
- **Reason**: Character history indicates she was Primogen before the Prince's death, but her position came under challenge. Misfortune is the current Malkavian Primogen (appointed when "the position opened" and "no other Malkavian wanted the responsibility"). This is a timeline issue - Lilith was the previous Primogen.

## References to Primogen (No Specific Character Named)

### Ventrue Primogen
- **Reference**: `reference/Plot Hooks/Warner Jefferson.md`
- **Mention**: Warner Jefferson is working for "Ventrue Primogen of Phoenix"
- **Status**: Position exists but no character assigned
- **Action**: Need to identify who the Ventrue Primogen is

### Tremere Primogen
- **Status**: No specific character mentioned
- **Action**: Check if position exists, identify character if needed

### Gangrel Primogen
- **Status**: No specific character mentioned
- **Action**: Check if position exists, identify character if needed

## Summary of Actions Needed

1. **Assign Alistaire as Nosferatu Primogen**
   - Create position if it doesn't exist: `primogen_nosferatu`
   - Assign character

2. **Assign Butch Reed as Brujah Primogen**
   - Position already exists: `primogen_brujah`
   - Assign character

3. **Investigate Ventrue Primogen**
   - Check if position exists
   - Identify character from database or character files
   - Assign if character exists

4. **Check for Tremere and Gangrel Primogen**
   - Verify if positions exist
   - Identify characters if positions are needed

## Notes

- **Lilith Nightshade** should NOT be assigned as she is a former Primogen (pre-Misfortune timeline)
- The script `database/add_primogen_assignments.php` can be used to create positions and assignments
- All assignments should use the default night: `1994-10-21 00:00:00`

