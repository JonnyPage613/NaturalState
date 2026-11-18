<?php
if(PHP_SAPI!=='cli')exit;
require dirname(__DIR__).'/src/bootstrap.php';
$driver=db()->getAttribute(PDO::ATTR_DRIVER_NAME);
$schema=file_get_contents(dirname(__DIR__).'/sql/schema.sql');
foreach(explode(';',$schema) as $sql)if(trim($sql))db()->exec($sql);
if($driver==='pgsql'){db()->exec('CREATE EXTENSION IF NOT EXISTS postgis');db()->exec('CREATE TABLE IF NOT EXISTS spatial_features (id VARCHAR(32) PRIMARY KEY, project_id VARCHAR(32) NOT NULL REFERENCES projects(id), kind VARCHAR(32) NOT NULL, geom geometry(Geometry,4326) NOT NULL)');db()->exec('CREATE INDEX IF NOT EXISTS spatial_features_geom ON spatial_features USING GIST(geom)');}
@mkdir($c['storage'].'/uploads',0700,true);
echo "Schema ready ($driver). No paid services enabled.\n";
