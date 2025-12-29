# Installation Instructions

## Option 1: Virtual Environment (Recommended)

1. **Create a virtual environment** (from project root):
   ```bash
   python -m venv venv
   ```

2. **Activate the virtual environment**:
   - **Windows (PowerShell):**
     ```powershell
     .\venv\Scripts\Activate.ps1
     ```
   - **Windows (CMD):**
     ```cmd
     venv\Scripts\activate.bat
     ```

3. **Install dependencies** (from anywhere, but activate venv first):
   ```bash
   pip install -r scripts/requirements.txt
   ```

4. **Run the script** (make sure venv is activated):
   ```bash
   python scripts/download_envato_images.py
   ```

## Option 2: Global Installation (Not Recommended)

You can install packages globally, but it's not recommended:

```bash
pip install -r scripts/requirements.txt
```

Then run:
```bash
python scripts/download_envato_images.py
```

## Why Virtual Environment?

- Keeps project dependencies isolated
- Prevents conflicts with other Python projects
- Makes it easier to manage versions
- Standard Python best practice

## Notes

- The script works from any directory - it finds the project root automatically
- The installation location doesn't matter - what matters is which Python environment runs the script
- Always activate your virtual environment before running the script

