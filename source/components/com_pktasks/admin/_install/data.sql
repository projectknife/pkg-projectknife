INSERT INTO `#__content_types` (`type_title`, `type_alias`, `table`, `rules`, `field_mappings`, `router`, `content_history_options`) 
VALUES(
	'Projectknife Task', 
	'com_pktasks.task', 
	'{"special":{"dbtable":"#__pk_tasks","key":"id","type":"Task","prefix":"PKTasksTable","config":"array()"},"common":{"dbtable": "#__ucm_content","key": "ucm_id","type": "Corecontent","prefix": "JTable","config": "array()"}}', 
	'', 
	'{"common":{"core_content_item_id":"id","core_title":"title","core_state":"published","core_alias":"alias","core_created_time":"created","core_modified_time":"modified","core_body":"description","core_hits":"null","core_publish_up":"null","core_publish_down":"null","core_access":"access","core_params":"null","core_featured":"null","core_metadata":"null","core_language":"null","core_images":"null","core_urls":"null","core_version":"null","core_ordering":"ordering","core_metakey":"null","core_metadesc":"null","core_catid":"null","core_xreference":"null","asset_id":"null"}}', 
	'', 
	''
);
