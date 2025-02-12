LATEST_TAG = 4.2.0
build:
	git archive --format=zip -o dist/cardinity-prestashop-$(LATEST_TAG).zip --prefix=cardinity/ HEAD