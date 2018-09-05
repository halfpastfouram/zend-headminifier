'use strict';

function firstFile() {
  return 'foo';
}

function bar(baz) {
  return baz;
}

document.addEventListener('load', function () {
  console.log(bar(firstFile()));
});