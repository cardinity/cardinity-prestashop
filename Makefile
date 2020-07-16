LATEST_TAG = $(shell git describe --abbrev=0 --tags)
build:
	git archive --format=zip -o dist/cardinity-prestashop-$(LATEST_TAG).zip --prefix=cardinity/ $(LATEST_TAG)