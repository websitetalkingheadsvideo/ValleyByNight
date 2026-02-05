@echo off
python -m pip install --user onnxruntime sentence-transformers
python -m pip list | findstr "onnxruntime sentence"
echo.
python migration.py
pause