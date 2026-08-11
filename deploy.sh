#!/usr/bin/env bash
#
# Actualización de AAMEVi en el servidor.
#
#   cd ~/aamevi.demosdesarrollos.com.ar && ./deploy.sh
#
# La puesta en marcha inicial (git init, nvm, .env, key:generate) está en
# docs/DEPLOY.md y solo se hace una vez. Este script cubre el ciclo repetido.
#
# Todo el cuerpo va dentro de { } a propósito: bash lee los scripts a medida que
# los ejecuta, y acá el propio `git pull` puede reescribir este archivo mientras
# corre. Con las llaves, bash parsea el bloque completo antes de empezar.

{
    set -euo pipefail

    cd "$(dirname "$0")"

    step() { printf '\n\033[1;36m==> %s\033[0m\n' "$1"; }
    fail() { printf '\n\033[1;31mERROR: %s\033[0m\n' "$1" >&2; exit 1; }

    step "Trayendo cambios"
    # --ff-only para que un deploy nunca genere un merge en el servidor
    git pull --ff-only

    step "Dependencias PHP"
    composer install --no-dev --optimize-autoloader --no-interaction

    step "Assets"
    # nvm no se carga solo en sesiones no interactivas
    if [ -s "$HOME/.nvm/nvm.sh" ]; then
        # shellcheck disable=SC1091
        . "$HOME/.nvm/nvm.sh"
        nvm use 22 >/dev/null 2>&1 || nvm use default >/dev/null 2>&1 || true
    fi

    command -v node >/dev/null 2>&1 || fail "No hay node. Instalalo con nvm (ver docs/DEPLOY.md paso 2)."

    # Vite 8 exige ^20.19.0 || >=22.12.0. El node del sistema es 18 y no sirve.
    node_major=$(node -v | sed 's/^v\([0-9]*\).*/\1/')
    if [ "$node_major" -lt 20 ]; then
        fail "Node $(node -v) es muy viejo para Vite 8. Corré: source ~/.nvm/nvm.sh && nvm use 22"
    fi

    echo "node $(node -v)"
    npm ci
    npm run build

    # Filament sirve sus propios assets desde public/, fuera del build de Vite.
    # No están versionados y hay que republicarlos en cada actualización.
    php artisan filament:assets

    step "Migraciones"
    php artisan migrate --force

    step "Cachés"
    # Regenerarlas no es opcional: con la caché activa Laravel deja de leer
    # .env, config/ y routes/, y serviría la versión anterior sin avisar.
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    step "Listo"
    php artisan about --only=environment
}
