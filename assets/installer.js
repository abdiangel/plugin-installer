jQuery(document).ready(function( $ ) {
    $('#install-action').on('click', function(e){

        var isLoading;
        if (isLoading == true){
            var wrapping = $('.wrap');
            wrapping.append('<div class="loader"></div>');
        }

        var data = {
            'action': 'mockTakePlugins',
            'plugins': ['jetpack'], // plugin_installer.plugins,
            // 'local_plugins': []
        };

          $.ajax({
            type: 'post',
            url: ajaxurl,
            data,
            success: function(data) {
                console.log(data);
                isLoading = false;
            },
            error: function(data) {
                console.log(data);
                isLoading = false;
            }
        });        
    });
    
	
});