import http from 'k6/http';
import { sleep, check } from 'k6';
import { expect, describe } from 'https://jslib.k6.io/k6chaijs/4.3.4.3/index.js';

export const options = {
  stages: [
    { duration: "1m", target: 1200 },
    { duration: "5m", target: 1200 },
    { duration: "1m", target: 0 },
  ],
  maxRedirects: 0, // do not connect to redirected url
};

// sudo docker run --rm --network=host -i grafana/k6 run - <read-test.js

// disable csrf protection to run this test
// or add _token and Cookie

function initCharSet() {
  let charSet = [];
  for (let i = 0; i < 10; i++) {
    charSet.push(String.fromCharCode(i + '0'.charCodeAt(0)));
  }
  for (let i = 0; i < 26; i++) {
    charSet.push(String.fromCharCode(i + 'A'.charCodeAt(0)));
  }
  for (let i = 0; i < 26; i++) {
    charSet.push(String.fromCharCode(i + 'a'.charCodeAt(0)));
  }
  return charSet;
}

function generateRandomInt(min, max) {
  // returns an int between min, max inclusive
  return min + Math.floor(Math.random() * (max + 1 - min));
}

function generateRandomString(charSet, len) {
  let randomString = "";
  for (let i = 0; i < len; i++) {
    randomString += charSet[generateRandomInt(0, charSet.length - 1)];
  }
  return randomString;
}

function chooseUrl(charSet) {
  const BASE_URL = "http://localhost:8000/";
  // some short_urls known to be stored in db
  // CONFIGURE BEFORE TEST
  const urls = [
    "ij3Hw6C",
    "F1QsUQs",
    "79mJHpG",
    "NTWWldt",
    "5JWqIFf"
  ];
  let p = Math.random();
  if (p > 0.75) {
    return `${BASE_URL}${generateRandomString(charSet, 7)}`;
  }
  // stored urls will be chosen 75% of the time
  return BASE_URL + urls[generateRandomInt(0, urls.length - 1)];
}

export default function() {
  const charSet = initCharSet();
  let res = http.get(chooseUrl(charSet));
  
  check(res, {"success": (r) => r.status === 302 || r.status === 200 /* page for not found */});
  check(res, {"not found": (r) => r.body.includes("Not Found")});

  sleep(1); 
}
