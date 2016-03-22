#!/bin/bash
#
# Checkout and update the branch on all repos
#
#
# ./language-differ.sh <to-branch> [<from-branch> ]
#
# - to-branch:		The branch that should receive the translations
# - from-branch:	Branch to get translations from

set -e

BASE_BRANCH=master
BACKPORT_BRANCH=$1

if [ "$2" ]; then
	BASE_BRANCH=$2
fi

L10N_FILES=$(git diff "$BACKPORT_BRANCH..$BASE_BRANCH" --name-status | grep "/l10n/" | grep ".json$" | grep "^M" | sed -e 's/M//g')

JSON_DELIMITER="\" : \""
L10N_DELIMITER="\/l10n\/"
for L10N_FILE in $L10N_FILES
do
	CHANGED_STRINGS=$(git diff "$BACKPORT_BRANCH..$BASE_BRANCH" "$L10N_FILE" | grep "^+    \"")
	L10N_PATH=($(echo $L10N_FILE | sed -e 's/'"$L10N_DELIMITER"'/\n/g' | while read line; do echo $line; done))
	L10N_FOLDER="${L10N_PATH[0]}"

	PREV_IFS=$IFS
	IFS=$'\n'
	for CHANGED_STRING in $CHANGED_STRINGS
	do
		CHANGED_STRING="${CHANGED_STRING:5}"
		ENGLISH_STRING=($(echo $CHANGED_STRING | sed -e 's/'"$JSON_DELIMITER"'/\n/g' | while read line; do echo $line; done))
		ENGLISH_STRING="${ENGLISH_STRING[0]}\""

		echo "\"$L10N_FOLDER\", $ENGLISH_STRING" >> find_strings
	done
	IFS=$PREV_IFS
done

