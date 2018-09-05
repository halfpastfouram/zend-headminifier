'use strict';

function secondFile() {
  return 'apple';
}

function orange(fruit) {
  return fruit;
}

document.addEventListener('load', function () {
  console.log(orange(secondFile()));
});