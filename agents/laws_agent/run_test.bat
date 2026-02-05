@echo off
cd /d C:\xampp\htdocs\agents\laws_agent
python test_script.py > output.txt 2>&1
notepad output.txt
pause