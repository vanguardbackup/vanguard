#!/usr/bin/env bash
set -eo pipefail

# Colour codes
GREEN='\033[0;32m'
LIGHT_GREEN='\033[1;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
YELLOW='\033[0;33m'
BOLD='\033[1m'
NC='\033[0m' # No Colour

LOG_FILE="bump_version.log"
VERBOSE=false

print_vanguard_logo() {
    printf "${MAGENTA}"
    printf " _    __                                     __\n"
    printf "| |  / /___ _____  ____ ___  ______ ______  / /\n"
    printf "| | / / __ \`/ __ \/ __ \`/ / / / __ \`/ ___/ / / \n"
    printf "| |/ / /_/ / / / / /_/ / /_/ / /_/ / /  _ / /  \n"
    printf "|___/\__,_/_/ /_/\__, /\__,_/\__,_/_/  (_)_/   \n"
    printf "                /____/                         \n"
    printf "${NC}\n"
}

print_fancy_header() {
    local title="$1"
    local width=60
    local line=$(printf '%*s' "$width" | tr ' ' '─')

    printf "${BLUE}┌${line}┐${NC}\n"
    printf "${BLUE}│ ${CYAN}%-$((width-2))s ${BLUE}│${NC}\n" "$title"
    printf "${BLUE}└${line}┘${NC}\n"
}

log() {
    local level="$1"
    local message="$2"
    local color=""

    case "$level" in
        "INFO") color="$CYAN" ;;
        "WARNING") color="$YELLOW" ;;
        "ERROR") color="$RED" ;;
        "SUCCESS") color="$GREEN" ;;
    esac

    if $VERBOSE || [ "$level" != "INFO" ]; then
        printf "${color}${BOLD}[$level]${NC} $message\n"
    fi
    echo "[$level] $message" >> "$LOG_FILE"
}

validate_semver() {
    if [[ $1 =~ ^v ]]; then
        log "ERROR" "Version cannot start with 'v'."
        exit 1
    elif [[ ! $1 =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        log "ERROR" "Version is not valid according to Semantic Versioning (SemVer)."
        exit 1
    fi
}

bump_version() {
    local version="$1"
    local bump_type="$2"

    IFS='.' read -r -a parts <<< "$version"

    case "$bump_type" in
        major)
            parts[0]=$((parts[0] + 1))
            parts[1]=0
            parts[2]=0
            ;;
        minor)
            parts[1]=$((parts[1] + 1))
            parts[2]=0
            ;;
        patch)
            parts[2]=$((parts[2] + 1))
            ;;
        *)
            log "ERROR" "Invalid bump type '$bump_type'. Use 'major', 'minor', or 'patch'."
            exit 1
            ;;
    esac

    echo "${parts[0]}.${parts[1]}.${parts[2]}"
}

update_feature_banner() {
    local version="$1"
    local title
    local description

    read -p "Enter the feature title: " title
    read -p "Enter the feature description: " description

    local json_content="[
    {
        \"title\": \"$title\",
        \"description\": \"$description\",
        \"version\": \"$version\",
        \"github_url\": \"https://github.com/vanguardbackup/vanguard/releases/tag/$version\"
    }
]"

    echo "$json_content" > new_features.json
    log "INFO" "Updated new_features.json with the latest feature information."
}

show_usage() {
    echo "Usage: $0 [-v] <major|minor|patch>"
    echo "  -v: Enable verbose mode"
}

# Parse command line arguments
while getopts "v" opt; do
    case ${opt} in
        v ) VERBOSE=true ;;
        \? ) show_usage; exit 1 ;;
    esac
done
shift $((OPTIND -1))

if [ $# -ne 1 ]; then
    show_usage
    exit 1
fi

BUMP_TYPE="$1"

print_vanguard_logo
print_fancy_header "Version Bump"

# Preliminary checks
if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    log "ERROR" "This directory is not a Git repository."
    exit 1
fi

if ! git diff --quiet --exit-code; then
    log "ERROR" "There are uncommitted changes in the repository. Please commit or stash them first."
    exit 1
fi

if [ ! -f VERSION ]; then
    log "ERROR" "VERSION file not found."
    exit 1
fi

OLD_VERSION=$(cat VERSION)
NEW_VERSION=$(bump_version "$OLD_VERSION" "$BUMP_TYPE")
validate_semver "$NEW_VERSION"

log "INFO" "Current version: ${CYAN}$OLD_VERSION${NC}"
log "INFO" "New version:     ${GREEN}$NEW_VERSION${NC}"

# Interactive version confirmation
echo
echo "Select the version to use:"
echo "1) $NEW_VERSION (recommended)"
echo "2) Enter custom version"
echo "3) Cancel"
read -p "Enter your choice (1-3): " choice

case $choice in
    1)
        log "INFO" "Using recommended version: $NEW_VERSION"
        ;;
    2)
        read -p "Enter custom version: " custom_version
        validate_semver "$custom_version"
        NEW_VERSION="$custom_version"
        log "INFO" "Using custom version: $NEW_VERSION"
        ;;
    3)
        log "INFO" "Version bump canceled."
        exit 0
        ;;
    *)
        log "ERROR" "Invalid choice. Exiting."
        exit 1
        ;;
esac

log "INFO" "Bumping version to $NEW_VERSION ..."
echo "$NEW_VERSION" > VERSION

# Ask if the user wants to update the feature banner
read -p "Do you want to update the feature banner? (y/n): " update_banner
if [[ $update_banner =~ ^[Yy]$ ]]; then
    update_feature_banner "$NEW_VERSION"
    git add new_features.json
fi

log "INFO" "Committing version bump..."
git add VERSION
git commit --no-verify -m "chore: bump version from $OLD_VERSION to $NEW_VERSION 🎉"

if git rev-parse -q --verify "refs/tags/$OLD_VERSION" >/dev/null; then
    log "WARNING" "Deleting old tag locally..."
    git tag -d "$OLD_VERSION"
fi

git tag "$NEW_VERSION"

log "INFO" "Pushing changes to GitHub..."
if ! git push --no-verify origin main "$NEW_VERSION"; then
    log "ERROR" "Failed to push changes to GitHub."
    exit 1
fi

log "SUCCESS" "Version bumped from $OLD_VERSION to $NEW_VERSION. 🚀"
echo
print_fancy_header "Version Bump Complete"
echo
log "INFO" "Next steps:"
log "INFO" "1. Create a new release on GitHub"
log "INFO" "2. Update any necessary documentation"
echo
log "SUCCESS" "Happy coding! 🎉👨‍💻👩‍💻"
