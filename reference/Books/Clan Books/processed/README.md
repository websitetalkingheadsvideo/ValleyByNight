# Processed clanbook text and RAG data

Clanbook final text (`.txt`) and RAG JSON (`.json`) produced for the laws agent. Source PDFs live in the parent folder (Clan Books).

- **`*_final.txt`** – Cleaned full text
- **`*_rag.json`** – Chunked documents for RAG import
- **`*_artifact_report.txt`** – Artifact review notes

Import a RAG file into the laws agent (from project root):

```powershell
php agents/laws_agent/import_rag_data.php "reference/Books/Clan Books/processed/<filename>.json"
```
