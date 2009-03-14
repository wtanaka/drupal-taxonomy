all: taxonomy.patch.bz2 taxonomy.tar.bz2

clean:
	find . -name "*~" -print -exec rm \{\} \;
	rm -f taxonomy.patch taxonomy.patch.bz2 taxonomy.tar taxonomy.tar.bz2

taxonomy.tar: modules/taxonomy/*.inc.php modules/taxonomy/taxonomy.module
	tar cvf "$@" $^

taxonomy.patch: modules/taxonomy/*
	git diff --no-prefix origin/vendor5 modules/taxonomy > "$@"

%.bz2: %
	bzip2 -9 -c $^ > "$@"
