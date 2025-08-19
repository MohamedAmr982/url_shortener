import http from 'k6/http';
import { sleep, check } from 'k6';
import { expect, describe } from 'https://jslib.k6.io/k6chaijs/4.3.4.3/index.js';

export const options = {
  vus: 10,
  duration: '1s',
};

// sudo docker run --rm --network=host -i grafana/k6 run - <concurrency-test.js

// disable csrf protection to run this test
// or add _token and Cookie

export default function() {
  let res = http.post(
    "http://localhost:8000/shorten", 
    {
      // make sure to insert a non-existing url to test concurrency
      url: "https://www.robot35.com",
      // _token: "" 
    },
    { 
      headers: {
      "Content-Type": "application/x-www-form-urlencoded",  
      // "Cookie": "" 
    } }
  );
  
  check(res, {"success": (r) => r.status === 200});
  // console.log(res.body);
  check(res, {"concurrency": (r) => r.body.includes("another tx just inserted the same url!")});
  check(res, {"collision": (r) => r.body.includes("collision occurred")});
  check(res, {"already exists": (r) => r.body.includes("already exists")});
  sleep(1); 
}
