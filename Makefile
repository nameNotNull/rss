NAME := $(shell basename $(shell git config --get remote.origin.url) | sed 's/\.git//')
BRANCH := $(shell git symbolic-ref --short HEAD 2>/dev/null)
VERSION := $(shell git log -1 --pretty=format:"%H")
#FILENAME=$(NAME)-$(VERSION)-$(BRANCH).tar.gz
FILENAME=$(NAME).tar.gz
TMPDIR=releases/tmp

default: all

all: pack

test: init
	echo "$(VERSION)" > $(TMPDIR)/xxx.xxx.xxx/VERSION
	(cd $(TMPDIR); tar czf $(FILENAME) xxx.xxx.xxx/)
	sh tests/test

init: clean
	mkdir -p $(TMPDIR)/xxx.xxx.xxx
	cp -r application config htdocs vendor $(TMPDIR)/xxx.xxx.xxx

pack: init
	echo "$(VERSION)" > $(TMPDIR)/xxx.xxx.xxx/VERSION
	(cd $(TMPDIR); tar czf $(FILENAME) xxx.xxx.xxx/)
	mv $(TMPDIR)/*.tar.gz releases/

clean:
	rm -rf $(TMPDIR)

distclean:
	rm -rf releases

.DEFAULT: all
