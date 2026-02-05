@echo off
cd /d C:\xampp\htdocs\agents\laws_agent
python -m pip install --user sentence-transformers
python migration.py
pause
python -m pip install --user onnxruntime
python -m pip list | findstr onnxruntime

python onnx.py
pause