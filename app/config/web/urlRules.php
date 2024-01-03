<?php

return [
	'OPTIONS <url:.+>' => 'site/options',
	'POST catalog/products' => 'catalog/admin/manage-product/create',
	'PATCH catalog/products/<id>' => 'catalog/admin/manage-product/patch',
	[
		'class' => 'yii\rest\UrlRule',
		'controller' => 'catalog/product',
		'patterns' => [
			'GET' => 'index',
			'HEAD' => 'calc-total',
			'GET,HEAD variants' => 'variants',
			'POST filter-fields-ranges' => 'filter-fields-ranges',
			'GET item/<id>' => 'view'
		]
	],
	'POST catalog/categories' => 'catalog/admin/manage-category/create',
	'PATCH catalog/categories/<id>' => 'catalog/admin/manage-category/patch',
	[
		'class' => 'yii\rest\UrlRule',
		'controller' => 'catalog/category',
		'patterns' => [
			'GET,HEAD tree' => 'tree',
			'GET,HEAD flat' => 'flat',
			'GET,HEAD item/<id>' => 'item',
			'GET parents' => 'parents',
		]
	],
	[
		'class' => 'yii\rest\UrlRule',
		'controller' => 'catalog/manufacturer',
		'patterns' => [
			'GET,HEAD' => 'index',
			'GET,HEAD <id>' => 'view',
		]
	],
	'GET catalog/product-types' => 'catalog/commodity-group/index',
	'GET catalog/product-types/<id>' => 'catalog/commodity-group/view',

	//outdated, should be removed in the next ver:
	[
		'class' => 'yii\rest\UrlRule',
		'controller' => 'catalog/commodity-group',
		'patterns' => [
			'GET,HEAD' => 'index',
			'GET,HEAD <id>' => 'view',
//			'GET,HEAD <id>/characteristics' => 'characteristics',
		]
	],
	'GET catalog/attributes' => 'catalog/characteristic/index',
	'GET catalog/attributes/<id>' => 'catalog/characteristic/view',
	'POST catalog/attributes' => 'catalog/admin/manage-characteristic/create',
	'PATCH catalog/attributes/<id>' => 'catalog/admin/manage-characteristic/patch',
	[
		'class' => 'yii\rest\UrlRule',
		'controller' => 'catalog/filter',
		'patterns' => [
			'GET,HEAD' => 'index',
			'GET by-category/<id>' => 'by-category',
			'GET <id>' => 'view',
		]
	],
	'PATCH orders/cart/<id>/qty' => 'orders/cart/patch-set-qty',
	'POST orders/cart/<id>/validate' => 'orders/cart/validate-stock',
	[
		'class' => 'yii\rest\UrlRule',
		'controller' => 'orders/cart',
		'pluralize' => false,
		'patterns' => [
			'POST retrieve' => 'retrieve',
			'POST add' => 'add',
			'POST rm-items' => 'rm-items',
			'POST add-custom-item' => 'add-custom-item',
			'GET tmp' => 'tmp',
			'GET <id>/total' => 'total',
			'GET <id>/items' => 'items',

			//legacy:
			'POST set-qty' => 'set-qty',
		]
	],
	'DELETE orders/checkout/<orderId>/discounts' => 'orders/checkout/delete-discounts',
	[
		'class' => 'yii\rest\UrlRule',
		'controller' => 'orders/checkout',
		'pluralize' => false,
		'patterns' => [
			'POST init' => 'init',
			'POST discount-code' => 'discount-code',

			//legacy:
			'POST clear-discounts' => 'clear-discounts',
			'POST contact' => 'contact',
		]
	],
	[
		'class' => 'yii\rest\UrlRule',
		'controller' => 'orders/checkout/shipping',
		'pluralize' => false,
		'patterns' => [
			'POST delivery-method' => 'delivery-method',
			'POST address' => 'address',
			'GET <id>' => 'page',
		]
	],
	'GET orders/checkout/<id>/shipping-step' => 'orders/checkout/shipping/page',
	'GET orders/checkout/<id>/payment-step' => 'orders/checkout/payment/page',
	'POST orders/checkout/<orderId>/place' => 'orders/checkout/orders/place',
	'PATCH orders/checkout/<orderId>/order' => 'orders/checkout/orders/update-attrs',
	'POST orders/checkout/<orderId>/contact' => 'orders/checkout/orders/contact',
//	'GET orders/checkout/<orderId>/payment-step' => 'orders/checkout/payment-step/step-info',
//	'PUT orders/checkout/<orderId>/payment' => 'orders/checkout/payment-step/save-payment',
	[
		'class' => 'yii\rest\UrlRule',
		'controller' => 'orders/checkout/payment',
		'pluralize' => false,
		'patterns' => [
			'POST set' => 'set',
			'POST paypal-capture' => 'paypal-capture',

			//legacy
			'GET <id>' => 'page',
		]
	],
	[
		'class' => 'yii\rest\UrlRule',
		'controller' => 'orders/customer/order',
		'pluralize' => false,
		'patterns' => [
			'GET get/<id>' => 'get-order',
			'POST set-custom-attrs' => 'set-custom-attrs',
			'POST make-payment-link' => 'make-payment-link',
			'POST set-addresses' => 'set-addresses',
		]
	],
	'GET orders/statuses' => 'orders/status/index',
	[
		'class' => 'yii\rest\UrlRule',
		'controller' => 'orders/customer/my-orders',
		'pluralize' => false,
		'patterns' => [
			'GET' => 'index',
		]
	],
	[
		'class' => 'yii\rest\UrlRule',
		'controller' => 'user/customer',
		'pluralize' => false,
		'patterns' => [
			'POST register' => 'register',
			'POST login' => 'login',
		]
	],
	[
		'class' => 'yii\rest\UrlRule',
		'controller' => 'user/auth',
		'pluralize' => false,
		'patterns' => [
			'POST validate-magick-link' => 'validate-magick-link',
			'POST mail-restore-link' => 'mail-restore-link',
		]
	],
	[
		'class' => 'yii\rest\UrlRule',
		'controller' => 'user/customer/private',
		'pluralize' => false,
		'patterns' => [
			'GET who-am-i' => 'who-am-i',
		]
	],
	[
		'class' => 'yii\rest\UrlRule',
		'controller' => 'user/customer/auth',
		'pluralize' => false,
		'patterns' => [
			'POST update-pass' => 'update-pass',
		]
	],
	'GET user/admin/customers' => 'user/admin/customer/index',
//	[
//		'class' => 'yii\rest\UrlRule',
//		'controller' => 'user/admin/customer',
//		'pluralize' => true,
//		'patterns' => [
//			'GET index' => 'index',
//		]
//	],
	'POST user/admin/auth/make-magick-link' => 'user/admin/auth/make-magick-link',
	'POST user/admin/auth/find-or-create' => 'user/admin/auth/find-or-create',
//	[
//		'class' => 'yii\rest\UrlRule',
//		'controller' => 'user/admin/auth',
//		'pluralize' => false,
//		'patterns' => [
//			'POST make-magick-link' => 'make-magick-link',
//		]
//	],
	[
		'class' => 'yii\rest\UrlRule',
		'controller' => 'orders/admin/orders',
		'pluralize' => false,
		'patterns' => [
			'GET' => 'index',
			'HEAD' => 'calc-total',
		]
	],
//	legacy:
//	[
//		'class' => 'yii\rest\UrlRule',
//		'controller' => 'orders/admin/order',
//		'pluralize' => false,
//		'patterns' => [
//			'PUT <id>' => 'update'
//		]
//	],
	'PATCH orders/admin/order/<orderId>' => 'orders/admin/order/patch-order',
	[
		'class' => 'yii\rest\UrlRule',
		'controller' => 'saas/wix',
		'pluralize' => false,
		'patterns' => [
			'POST install' => 'install',
			'POST make-api-token-by-instance' => 'make-api-token-by-instance',
		]
	],
	'GET system/settings' => 'system/settings/fetch',
	[
		'class' => 'yii\rest\UrlRule',
		'controller' => 'system/settings',
		'pluralize' => false,
		'patterns' => [
			'GET wix-site' => 'wix-site',
			'POST wix-site' => 'save-wix-site',
			'GET fetch' => 'fetch',
		]
	],
	'POST files/images/upload' => 'files/admin/manage-images/upload',
	'GET files/images/<imageId>/original-url' => 'files/admin/manage-images/original-url',
	'GET system/countries' => 'system/country/index',
];
