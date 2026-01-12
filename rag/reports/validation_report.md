# Validation Report

## Chunk Validation

- **Total chunks**: 2788
- **Valid chunks**: 2788
- **Schema errors**: 0
- **Duplicate chunk IDs**: 0
- **Duplicate anchors (by book)**: 0
- **Oversized chunks (>1500 tokens)**: 794
- **Undersized chunks (<50 tokens)**: 3
- **Invalid book references**: 0
- **Invalid glossary references**: 0
- **Missing heading paths**: 2

### Oversized Chunks

- `anarch_guide_9952e374_1`: 5030 tokens
- `anarch_guide_ce1cbd74_87`: 4037 tokens
- `anarch_guide_63e6e8a5_131`: 2086 tokens
- `anarch_guide_5494339b_151`: 2510 tokens
- `anarch_guide_552cf960_183`: 4481 tokens
- `anarch_guide_cd03a516_250`: 1628 tokens
- `anarch_guide_43686e3a_262`: 4351 tokens
- `anarch_guide_1cbc9182_298`: 1609 tokens
- `anarch_guide_889bf7db_338`: 5097 tokens
- `anarch_guide_ef9e70e5_380`: 1678 tokens
- `anarch_guide_1cc8b09d_396`: 5764 tokens
- `anarch_guide_9d84c46f_448`: 3409 tokens
- `anarch_guide_69cc7150_478`: 1657 tokens
- `anarch_guide_572b0b34_490`: 7134 tokens
- `anarch_guide_1ee05b04_546`: 3250 tokens
- `anarch_guide_c3a6ed07_576`: 2509 tokens
- `anarch_guide_208f440e_614`: 3095 tokens
- `anarch_guide_2d20d638_694`: 2627 tokens
- `anarch_guide_51df7aa7_776`: 3908 tokens
- `anarch_guide_bd80e74c_798`: 9017 tokens

## Glossary Validation

- **Total terms**: 0
- **Valid terms**: 0
- **Schema errors**: 1
- **Duplicate terms**: 0
- **Empty aliases**: 0

### Schema Errors

- None is not of type 'string'

Failed validating 'type' in schema['properties']['terms']['items']['properties']['short_definition']:
    {'type': 'string', 'description': 'Optional brief definition'}

On instance['terms'][3838]['short_definition']:
    None

## Summary

⚠️ **Found 795 validation issues**
