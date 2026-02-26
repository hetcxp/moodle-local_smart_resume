#!/bin/bash

OUTPUT="smart_resume_compressed.txt"

# 1. Mapa compacto
echo "> MAPA:" > $OUTPUT
find . -type f \( -name "*.php" -o -name "*.xml" -o -name "*.mustache" -o -name "*.js" \) -not -name "*.min.js" -not -path "*/node_modules/*" -not -path "*/vendor/*" -not -path "*/.*" | sort >> $OUTPUT

# 2. Código fuente comprimido (sin líneas vacías ni indentación)
echo -e "\n> CÓDIGO:" >> $OUTPUT
find . -type f \( -name "*.php" -o -name "*.xml" -o -name "*.mustache" -o -name "*.js" \) -not -name "*.min.js" -not -path "*/node_modules/*" -not -path "*/vendor/*" -not -path "*/.*" -exec sh -c '
    # Separador minimalista
    echo "\n--- {}: ---"
    # awk elimina líneas vacías y borra espacios/tabs al inicio de cada línea
    awk "NF > 0 { gsub(/^[ \t]+/, \"\"); print }" "{}"
' \; >> $OUTPUT

echo "¡Contexto comprimido y listo en $OUTPUT!"