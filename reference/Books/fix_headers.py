# Fix LotNR-formatted.md headers with Unicode apostrophes
# File uses CRLF and Unicode apostrophe U+2019
import codecs

path = r'v:\reference\Books\LotNR-formatted.md'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

# File has \n line endings
nl = "\n"
apo = "\u2019"  # Unicode right single quotation (smart apostrophe)

replacements = [
    ("Ancilla — A Kindred", "**Ancilla** — A Kindred"),
    (f"Beast, The — The personification of the vampire{apo}s predatory nature", f"**Beast, The** — The personification of the vampire{apo}s predatory nature"),
    (f"Blood — A vampire{apo}s heritage", f"**Blood** — A vampire{apo}s heritage"),
    (f"Domain —An area of a particular vampire{apo}s influence", f"**Domain** — An area of a particular vampire{apo}s influence"),
    (f"Haven — A vampire{apo}s home or where he sleeps during the day.", f"**Haven** — A vampire{apo}s home or where he sleeps during the day."),
    (f"Primogen — A city{apo}s ruling council of elders.", f"**Primogen** — A city{apo}s ruling council of elders."),
]

for old, new in replacements:
    if old in content:
        content = content.replace(old, new)
        print("Fixed:", old[:50].replace("\r", "\\r").replace("\n", "\\n"))
    else:
        print("Not found:", repr(old[:60]))

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
print("Done")
