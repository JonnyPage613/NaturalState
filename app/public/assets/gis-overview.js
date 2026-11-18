/* Interactive public-data walkthrough. Job features and truck motion are fictional. */
'use strict';
(() => {
  const $ = id => document.getElementById(id);
  const loading = $('map-loading');
  const center = [-93.93883, 36.284877];
  const empty = {type:'FeatureCollection',features:[]};
  const controls = [...document.querySelectorAll('.layer-dock button')];
  const state = {base:'topo', parcels:true, soils:false, field:true, parcelMessage:'Loading published parcels…', playing:false, site:false, soilMessage:'USDA soil-map-unit boundaries'};
  let map, truck, timer, ready=false, routeProgress=0, lastTick=0;
  const route = [[-93.9392,36.28380],[-93.9390,36.28390],[-93.9388,36.28405],[-93.93878,36.28420],[-93.93883,36.28450]];
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

  function status(){
    const bits = [state.base==='topo'?'USGS topo':'USGS imagery'];
    if(state.parcels) bits.push(state.parcelMessage);
    if(state.soils) bits.push(state.soilMessage);
    if(state.field) bits.push('Fictional field flags / 100-ft well ring');
    $('layer-status').textContent=bits.join(' · ');
  }
  function fail(message){loading.hidden=false;loading.textContent=message;}
  function circle(point, feet=100){
    const rad=Math.PI/180,lat=point[1]*rad,lon=point[0]*rad,d=feet*.3048/6371008.8,ring=[];
    for(let i=0;i<64;i++){
      const b=i*2*Math.PI/64,y=Math.asin(Math.sin(lat)*Math.cos(d)+Math.cos(lat)*Math.sin(d)*Math.cos(b));
      const x=lon+Math.atan2(Math.sin(b)*Math.sin(d)*Math.cos(lat),Math.cos(d)-Math.sin(lat)*Math.sin(y));
      ring.push([x/rad,y/rad]);
    }
    ring.push([...ring[0]]);return {type:'Feature',properties:{},geometry:{type:'Polygon',coordinates:[ring]}};
  }
  function marker(label,point,text,well=false){
    const element=document.createElement('button');element.type='button';
    element.className='field-flag'+(well?' well':'');element.textContent=label;
    element.setAttribute('aria-label',text);
    const popup=new maplibregl.Popup({offset:20}).setText(text);
    return new maplibregl.Marker({element}).setLngLat(point).setPopup(popup).addTo(map);
  }
  function truckPoint(t){
    const scaled=t*(route.length-1),index=Math.min(Math.floor(scaled),route.length-2),part=scaled-index;
    return route[index].map((v,i)=>v+(route[index+1][i]-v)*part);
  }
  function stop(){
    state.playing=false;if(timer)cancelAnimationFrame(timer);timer=null;
    $('trip-toggle').textContent='Play truck demo';$('trip-toggle').setAttribute('aria-pressed','false');
  }
  function play(){
    if(!ready)return;
    state.playing=true;lastTick=0;$('trip-toggle').textContent='Pause truck demo';$('trip-toggle').setAttribute('aria-pressed','true');
    function tick(now){
      if(!state.playing)return;
      if(lastTick)routeProgress=(routeProgress+Math.min(now-lastTick,100)/24000)%1;
      lastTick=now;truck.setLngLat(truckPoint(routeProgress));timer=requestAnimationFrame(tick);
    }
    timer=requestAnimationFrame(tick);
  }
  async function parcels(){
    const query=new URLSearchParams({f:'geojson',geometry:'-93.947,36.280,-93.931,36.290',geometryType:'esriGeometryEnvelope',inSR:'4326',outSR:'4326',spatialRel:'esriSpatialRelIntersects',outFields:'parcelid,county,sourcedate',returnGeometry:'true',resultRecordCount:'100'});
    try{
      const response=await fetch('https://gis.arkansas.gov/arcgis/rest/services/FEATURESERVICES/Planning_Cadastre/FeatureServer/6/query?'+query,{signal:AbortSignal.timeout(15000)});
      const data=await response.json();
      if(!response.ok||data.error||data.type!=='FeatureCollection'||!Array.isArray(data.features))throw Error('Parcel service');
      map.getSource('parcels').setData(data);
      state.parcelMessage=data.features.length?data.features.length+' published Arkansas parcel outlines':'Parcel service returned no outlines here';
    }catch(e){state.parcelMessage='Parcel service unavailable—try this layer again';}
    status();
  }
  try{
    if(typeof maplibregl==='undefined'){
      fail('Interactive map needs WebGL. Open the field app on a supported device; the plan below remains available.');return;
    }
    map=new maplibregl.Map({container:'overview-map',center,zoom:15.6,attributionControl:true,
      style:{version:8,sources:{topo:{type:'raster',tiles:['https://basemap.nationalmap.gov/arcgis/rest/services/USGSTopo/MapServer/tile/{z}/{y}/{x}'],tileSize:256,maxzoom:16,attribution:'USGS The National Map'}},layers:[{id:'base',type:'background',paint:{'background-color':'#f3e9d6'}},{id:'topo',type:'raster',source:'topo'}]}});
    map.addControl(new maplibregl.NavigationControl({showCompass:false}),'top-right');
    map.addControl(new maplibregl.ScaleControl({unit:'imperial',maxWidth:95}),'bottom-left');
    const loadTimeout=setTimeout(()=>{if(!ready)fail('The public map is taking longer to load. You can still read the plan or open the field app below.');},18000);
    map.on('error',e=>{
      if(!ready)fail('A public map resource is unavailable. The workflow details and field-app link remain available.');
      else if(e.sourceId==='soils'){
        state.soilMessage='USDA soil service temporarily unavailable';status();map.setPaintProperty('topo','raster-saturation',0);map.setPaintProperty('topo','raster-brightness-max',1);
      }else if(e.sourceId==='topo'||e.sourceId==='aerial'){
        $('layer-status').textContent='A USGS map tile is unavailable. Try the other base map; recorded features remain visible.';
      }
    });
    map.on('load',()=>{
      clearTimeout(loadTimeout);
      map.addSource('aerial',{type:'raster',tiles:['https://basemap.nationalmap.gov/arcgis/rest/services/USGSImageryOnly/MapServer/tile/{z}/{y}/{x}'],tileSize:256,maxzoom:20,attribution:'USGS imagery'});
      map.addLayer({id:'aerial',type:'raster',source:'aerial',layout:{visibility:'none'}});
      map.addSource('soils',{type:'raster',tiles:['https://sdmdataaccess.sc.egov.usda.gov/Spatial/SDM.wms?SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap&LAYERS=mapunitpoly&STYLES=&FORMAT=image/png&TRANSPARENT=TRUE&SRS=EPSG:3857&BBOX={bbox-epsg-3857}&WIDTH=256&HEIGHT=256'],tileSize:256,attribution:'USDA NRCS Soil Data Access'});
      map.addLayer({id:'soils',type:'raster',source:'soils',layout:{visibility:'none'},paint:{'raster-opacity':.95}});
      map.addSource('parcels',{type:'geojson',data:empty});
      map.addLayer({id:'parcel-fill',type:'fill',source:'parcels',paint:{'fill-color':'#b77642','fill-opacity':.1}});
      map.addLayer({id:'parcel-line',type:'line',source:'parcels',paint:{'line-color':'#b77642','line-width':2.5}});
      map.addSource('demo-route',{type:'geojson',data:{type:'Feature',properties:{},geometry:{type:'LineString',coordinates:route}}});
      map.addLayer({id:'demo-route',type:'line',source:'demo-route',paint:{'line-color':'#3b4629','line-width':4,'line-dasharray':[2,1]}});
      const well=[-93.93795,36.28525];
      map.addSource('demo-buffer',{type:'geojson',data:circle(well)});
      map.addLayer({id:'well-fill',type:'fill',source:'demo-buffer',paint:{'fill-color':'#3b4629','fill-opacity':.15}});
      map.addLayer({id:'well-line',type:'line',source:'demo-buffer',paint:{'line-color':'#3b4629','line-width':2,'line-dasharray':[3,2]}});
      const flags=[marker('P1',[-93.9390,36.28502],'P1 · Fictional test-pit location; no excavation at this public site.'),marker('P2',[-93.93965,36.28460],'P2 · Fictional test-pit location.'),marker('W',well,'W · Fictional well position with a 100-foot planning radius.',true)];
      const truckElement=document.createElement('div');truckElement.className='truck-marker';
      truckElement.setAttribute('role','img');truckElement.setAttribute('aria-label','Truck T01 · simulated location');
      truckElement.innerHTML='<svg viewBox="0 0 32 24" aria-hidden="true"><path d="M2 5h17v13H2zM19 10h6l5 5v3H19zM25 10v5h5"/><circle cx="8" cy="19" r="3"/><circle cx="25" cy="19" r="3"/></svg>';
      truck=new maplibregl.Marker({element:truckElement}).setLngLat(route[0]).setPopup(new maplibregl.Popup({offset:22}).setText('Truck T01 · equipment trailer · fictional trip for FIELD-101. Live GPS connects in the vehicle pilot.')).addTo(map);
      ready=true;loading.hidden=true;controls.forEach(button=>button.disabled=false);
      document.querySelectorAll('[data-base]').forEach(button=>button.onclick=()=>{
        state.base=button.dataset.base;
        map.setLayoutProperty('aerial','visibility',state.base==='aerial'?'visible':'none');
        document.querySelectorAll('[data-base]').forEach(b=>b.setAttribute('aria-pressed',String(b===button)));status();
      });
      document.querySelectorAll('[data-layer]').forEach(button=>button.onclick=()=>{
        const key=button.dataset.layer;state[key]=!state[key];button.setAttribute('aria-pressed',String(state[key]));
        const visible=state[key]?'visible':'none';
        if(key==='parcels'){
          ['parcel-fill','parcel-line'].forEach(id=>map.setLayoutProperty(id,'visibility',visible));
          if(state.parcels&&state.parcelMessage.includes('unavailable'))parcels();
        }
        if(key==='soils'){
          map.setLayoutProperty('soils','visibility',visible);
          map.setPaintProperty('topo','raster-saturation',state.soils&&!state.soilMessage.includes('unavailable')?-1:0);
          map.setPaintProperty('topo','raster-brightness-max',state.soils&&!state.soilMessage.includes('unavailable') ? .7 : 1);
        }
        if(key==='field'){
          flags.forEach(flag=>flag.getElement().hidden=!state.field);
          ['well-fill','well-line'].forEach(id=>map.setLayoutProperty(id,'visibility',visible));
        }
        status();
      });
      $('trip-toggle').onclick=()=>state.playing?stop():play();
      $('site-view').onclick=()=>{
        state.site=!state.site;map.easeTo({center,zoom:state.site?17.5:15.6,duration:reduced.matches?0:550});
        $('site-view').textContent=state.site?'Show overview':'Zoom to pits';
      };
      parcels();status();
      if(!reduced.matches)play();
      reduced.addEventListener('change',e=>{if(e.matches)stop();});
      document.addEventListener('visibilitychange',()=>{if(document.hidden)stop();});
    });
  }catch(e){console.error('GIS overview initialization:',e);stop();controls.forEach(button=>button.disabled=true);fail('Map unavailable on this device. Open the field app or continue with the plan below.');}
})();
