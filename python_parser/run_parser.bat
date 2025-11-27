@echo off
CALL "%~dp0\venv\Scripts\activate.bat"
python -X utf8 "%~dp0\parse_visa_pdf.py" %*
CALL "%~dp0\venv\Scripts\deactivate.bat"