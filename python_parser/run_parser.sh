#!/bin/bash
source "$(dirname "$0")/venv/bin/activate"
python -X utf8 "$(dirname "$0")/parse_visa_pdf.py" "$@"
deactivate