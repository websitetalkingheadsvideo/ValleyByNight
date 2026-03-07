# Fix discipline subheaders: BasiC -> ### Basic, etc.
import re

path = r'v:\reference\Books\LotNR-formatted.md'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

# Pattern: "BasiC discipline" or "intermediate discipline" or "advanCed discipline" at start of line
# Convert to ### Basic Discipline, ### Intermediate Discipline, ### Advanced Discipline
patterns = [
    (r'^BasiC ([a-z][a-z]+)', r'### Basic \1'),
    (r'^intermediate ([a-z][a-z]+)', r'### Intermediate \1'),
    (r'^advanCed ([a-z][a-z]+)', r'### Advanced \1'),
    (r'^BasiC ([A-Z][a-zA-Z ]+)', r'### Basic \1'),
    (r'^intermediate ([A-Z][a-zA-Z ]+)', r'### Intermediate \1'),
    (r'^advanCed ([A-Z][a-zA-Z ]+)', r'### Advanced \1'),
]

for pat, repl in patterns:
    content, n = re.subn(pat, repl, content, flags=re.MULTILINE)
    if n:
        print(f"Replaced {n}: {pat[:20]}...")

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
print("Done")
