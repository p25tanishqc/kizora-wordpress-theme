#!/bin/bash
# Builds dist/kizora-theme.zip, ready for Appearance → Themes → Add New → Upload Theme.
set -e
cd "$(dirname "$0")/.."
mkdir -p dist
rm -f dist/kizora-theme.zip
zip -rq dist/kizora-theme.zip kizora -x "*.DS_Store"
echo "dist/kizora-theme.zip ($(du -h dist/kizora-theme.zip | cut -f1))"
