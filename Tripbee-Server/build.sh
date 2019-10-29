#!/bin/sh
cd $TRAVIS_BUILD_DIR/api
npm install
cd $TRAVIS_BUILD_DIR/ui
npm install
npm run build