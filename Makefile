LATEST_TAG = 4.0.3.1
build:
	git archive --format=zip -o dist/cardinity-prestashop-$(LATEST_TAG).zip --prefix=cardinity/ HEAD