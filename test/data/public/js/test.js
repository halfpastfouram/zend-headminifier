'use strict';

function test123() {
  return 'foo';
}

function bar(baz) {
  return baz;
}

document.addEventListener('load', function () {
  console.log(bar(test123()));
});