jQuery(function($){
	$('.ms-player.single audio').mediaelementplayer({
		features: ['playpause','current','progress','duration','volume'],
		videoVolume: 'horizontal',
		iPadUseNativeControls: false,
		iPhoneUseNativeControls: false
	});
	
	$('.ms-player.multiple audio').mediaelementplayer({
		features: ['playpause'],
		iPadUseNativeControls: false,
		iPhoneUseNativeControls: false
	});
});

