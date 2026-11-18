'use strict';
const assert=require('node:assert/strict');
const geo=require('../hosting/public_html/assets/workflow/site-workspace.js');
let count=0;
for(const center of [[-93.93883,36.284877],[-94.12,36.31],[179.9999,60],[-122,80]]){
 const ring=geo.circle(center).coordinates[0];assert.deepEqual(ring[0],ring.at(-1));
 for(const point of ring){assert.ok(Math.abs(geo.distance(center,point)-100)<0.0001);assert.ok(point[0]>=-180&&point[0]<=180);}count++;
}
assert.equal(geo.distance([-94,36],[-94,36]),0);count++;
assert.equal(geo.anniversary('2024-02-29'),'2025-02-28');count++;
assert.equal(geo.anniversary('2026-11-16'),'2027-11-16');count++;
assert.equal(geo.anniversary('2026-02-30'),null);count++;
console.log(JSON.stringify({passed:count,checks:'100-ft geodesic rings, closed geometry, high latitudes/dateline, leap-year anniversary and invalid dates'}));
