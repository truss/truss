VERSION=$(shell cat VERSION)


deploy_bump:
	sed 's/"dev-master": "[^"]*"/"dev-master": "${VERSION}"/' composer.json > composer.json.tmp
	rm composer.json
	mv composer.json.tmp composer.json

deploy_github:
	git commit -am 'version ${VERSION}'
	git tag ${VERSION}
	git push github ${VERSION}
	git push github master

deploy: deploy_bump deploy_github
