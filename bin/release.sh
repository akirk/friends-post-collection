#!/bin/bash
cd "$(dirname "$0")/.."
PLUGIN_VERSION=$(grep -E "Version:" friends-post-collection.php | grep -Eo "[0-9]+\.[0-9]+\.[0-9]+")

echo Friends Post Collection Release $PLUGIN_VERSION
echo "==========================================="
echo

git symbolic-ref --short HEAD | grep -q ^main$
if [ $? -eq 1 ]; then
	echo -ne "\033[31m✘\033[0m "
	echo "Not on git branch main"
	exit 1
fi
echo -ne "\033[32m✔\033[0m "
echo "On git branch main"

if $(git tag | grep -Eq ^$PLUGIN_VERSION\$); then
	echo -ne "\033[31m✘\033[0m "
	echo "Tag $PLUGIN_VERSION already exists"

	echo -n "Which version number shall this version get? "
	read NEW_VERSION

	if ! echo $NEW_VERSION | grep -Eq "^[0-9]+\.[0-9]+\.[0-9]+$"; then
		echo -ne "\033[31m✘\033[0m "
		echo "Invalid version number $NEW_VERSION"
		exit 1
	fi

	if [ -f new-changelog.md ]; then
		rm new-changelog.md
	fi

	prs=$(git log $PLUGIN_VERSION..main --pretty=format:"- %s")
	echo "## $NEW_VERSION" > new-changelog.md
	echo -e "$prs" >> new-changelog.md

	if [ -n "$VISUAL" ]; then
		CMD="${VISUAL%% *}"
		ARGS="${VISUAL#* }"
		$CMD $ARGS new-changelog.md
	else
		${EDITOR:-nano} new-changelog.md
	fi

	if [ $? -eq 1 ]; then
		echo -ne "\033[31m✘\033[0m "
		echo "Failed to open editor"

		echo "This is the generated changelog:"
		cat new-changelog.md
		echo -n "Do you want to continue? [Y/n] "
		read

		if [ "$REPLY" == "n" ]; then
			exit 1
		fi
	fi

	links=""
	for link in $(grep -Eo "#[0-9]+" new-changelog.md | sort | uniq); do
		links="$links\n[$link]: https://github.com/akirk/friends-post-collection/pull/${link:1}"
	done

	cat new-changelog.md | sed -e "s/\(#[0-9]\{1,6\}\)/[\1]/g" > new-changelog.tmp
	echo >> new-changelog.tmp

	cp new-changelog.tmp CHANGELOG.new
	cat CHANGELOG.md >> CHANGELOG.new
	echo -e "$links" >> CHANGELOG.new
	mv CHANGELOG.new CHANGELOG.md

	echo -ne "\033[32m✔\033[0m "
	echo "Changelog updated in CHANGELOG.md"

	sed -i -e '/## Changelog/{n
r new-changelog.tmp
}' README.md

	rm -f README.md-e new-changelog.tmp
	echo -e "$links" >> README.md

	echo -ne "\033[32m✔\033[0m "
	echo "Changelog updated in README.md"

	sed -i -e "s/Version: $PLUGIN_VERSION/Version: $NEW_VERSION/" friends-post-collection.php
	rm -f friends-post-collection.php-e

	echo -ne "\033[32m✔\033[0m "
	echo "Version updated in friends-post-collection.php"

	sed -i -e "s/Stable tag: $PLUGIN_VERSION/Stable tag: $NEW_VERSION/" README.md
	rm -f README.md-e

	echo -ne "\033[32m✔\033[0m "
	echo "Stable tag updated in README.md"

	echo -n "❯ git diff CHANGELOG.md README.md friends-post-collection.php"
	read
	git diff CHANGELOG.md README.md friends-post-collection.php

	echo -n "Are you happy with the changes? [y/N] "
	read

	if [ "$REPLY" != "y" ]; then
		echo "You can revert the changes with"
		echo
		echo "❯ git checkout CHANGELOG.md README.md friends-post-collection.php"
		echo
		read
		git checkout CHANGELOG.md README.md friends-post-collection.php
		exit 1
	fi
	rm -f new-changelog.md

	echo -n "❯ git add CHANGELOG.md README.md friends-post-collection.php"
	read
	git add CHANGELOG.md README.md friends-post-collection.php

	echo -n "❯ git commit -m \"Version bump + Changelog\""
	read
	git commit -m "Version bump + Changelog"

	echo -n "❯ git push"
	read
	git push

	echo "Restart the script to continue"
	exit 1
fi
echo -ne "\033[32m✔\033[0m "
echo "Tag $PLUGIN_VERSION doesn't exist yet"

grep -q "Stable tag: $PLUGIN_VERSION" README.md
if [ $? -eq 1 ]; then
	echo -ne "\033[31m✘\033[0m "
	echo "Stable tag not updated in README.md:"
	awk '/Stable tag: / { print "  " $0 }' README.md
	exit 1
fi
echo -ne "\033[32m✔\033[0m "
echo "Stable tag updated in README.md:"
awk '/Stable tag: / { print "  " $0 }' README.md

grep -q "Version: $PLUGIN_VERSION" friends-post-collection.php
if [ $? -eq 1 ]; then
	echo -ne "\033[31m✘\033[0m "
	echo "Version not updated in friends-post-collection.php:"
	awk '/Version: / { print "  " $0 }' friends-post-collection.php
	exit 1
fi
echo -ne "\033[32m✔\033[0m "
echo "Version updated in friends-post-collection.php:"
awk '/Version: / { print "  " $0 }' friends-post-collection.php

grep -q "## $PLUGIN_VERSION" CHANGELOG.md
if [ $? -eq 1 ]; then
	echo -ne "\033[31m✘\033[0m "
	echo "Changelog not found in CHANGELOG.md"
	exit 1
fi
echo -ne "\033[32m✔\033[0m "
echo "Changelog updated in CHANGELOG.md:"
awk '/^## '$PLUGIN_VERSION'/ { print "  " $0; show = 1; next } /^##/ { show = 0 } { if ( show ) print "  " $0 }' CHANGELOG.md

grep -q "### $PLUGIN_VERSION" README.md
if [ $? -eq 1 ]; then
	echo -ne "\033[31m✘\033[0m "
	echo "Changelog not found in README.md"
	exit 1
fi
echo -ne "\033[32m✔\033[0m "
echo "Changelog updated in README.md:"
awk '/^### '$PLUGIN_VERSION'/ { print "  " $0; show = 1; next } /^###/ { show = 0 } { if ( show ) print "  " $0 }' README.md

git diff-files --quiet
if [ $? -eq 1 ]; then
	echo -ne "\033[31m✘\033[0m "
	echo "Unstaged changes in git"
	echo
	echo ❯ git status
	git status
	exit 1
fi
echo -ne "\033[32m✔\033[0m "
echo "No unstaged changes in git"

echo
echo -ne "\033[32m✔\033[0m "
echo "All looks good, ready to tag and push!"
echo -n ❯ git tag $PLUGIN_VERSION
read
git tag $PLUGIN_VERSION
echo -n ❯ git push origin $PLUGIN_VERSION
read
git push origin $PLUGIN_VERSION

echo
echo -ne "\033[32m✔\033[0m "
echo "Tag $PLUGIN_VERSION pushed! GitHub Action will build the release."
echo
echo "Watch the progress at:"
echo "https://github.com/akirk/friends-post-collection/actions"
