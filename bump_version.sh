#!/bin/bash

set -e

LOG_FILE="bump_version.log"
VERBOSE=false

log() {
    if $VERBOSE; then
        echo "$1"
    fi
    echo "$1" >> "$LOG_FILE"
}

validate_semver() {
    if [[ $1 =~ ^v ]]; then
        log "Error: Version cannot start with 'v'."
        exit 1
    elif [[ ! $1 =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        log "Error: Version is not valid according to Semantic Versioning (SemVer)."
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
            log "Error: Invalid bump type '$bump_type'. Use 'major', 'minor', or 'patch'."
            exit 1
            ;;
    esac

    echo "${parts[0]}.${parts[1]}.${parts[2]}"
}

show_usage() {
    echo "Usage: $0 [-v] <major|minor|patch>"
    echo "  -v: Enable verbose mode"
}

# Check for verbose flag
while getopts "v" opt; do
    case ${opt} in
        v )
            VERBOSE=true
            ;;
        \? )
            show_usage
            exit 1
            ;;
    esac
done
shift $((OPTIND -1))

if [ $# -ne 1 ]; then
    show_usage
    exit 1
fi

BUMP_TYPE="$1"

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    log "Error: This directory is not a Git repository."
    exit 1
fi

if ! git diff --quiet --exit-code; then
    log "Error: There are uncommitted changes in the repository. Please commit or stash them first."
    exit 1
fi

if [ ! -f VERSION ]; then
    log "Error: VERSION file not found."
    exit 1
fi

OLD_VERSION=$(cat VERSION)
NEW_VERSION=$(bump_version "$OLD_VERSION" "$BUMP_TYPE")
validate_semver "$NEW_VERSION"

log "Old Version: $OLD_VERSION"
log "New Version: $NEW_VERSION"

read -r -p "Are you sure you want to bump the version from $OLD_VERSION to $NEW_VERSION? [y/n]: " confirm
if [ "$confirm" != "y" ]; then
    log "Version bump canceled."
    exit 0
fi

log "Bumping version to $NEW_VERSION ..."
echo "$NEW_VERSION" > VERSION

log "Committing version bump..."
git add VERSION
git commit -m "Bump version from $OLD_VERSION to $NEW_VERSION"

if git rev-parse -q --verify "refs/tags/$OLD_VERSION" >/dev/null; then
    log "Deleting old tag locally..."
    git tag -d "$OLD_VERSION"
fi

git tag "$NEW_VERSION"

log "Pushing changes to GitHub..."
if ! git push origin main "$NEW_VERSION"; then
    log "Error: Failed to push changes to GitHub."
    exit 1
fi

log "Version bumped from $OLD_VERSION to $NEW_VERSION."
echo "Version bumped from $OLD_VERSION to $NEW_VERSION."
