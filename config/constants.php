<?php

return [
	'APP_NAME' => 'TB Care',
	'UPLOAD_PATH' => strpos(env('APP_URL'), 'https://') === 0 ? base_path('../public_html/upload_images') : public_path('upload_images')
];