(function($){
    $( document ).on( 'keyup', '.search-user-behaviour', function() {
        search_term = $.trim( $(this).val());
        if(search_term == ''){
            $('.qsm-user-behaviour-page tbody tr').each(function(){
                $(this).show()
            });
        } else {
            search_term = new RegExp(search_term, 'i');
            $('.qsm-user-behaviour-page tbody tr').each(function(){
                search_string = $(this).text();
                result = search_string.search(search_term);
                if(result > -1){
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            
        }
        countRows();
    });

    $(document).ready(function(){
        countRows();
    });

    function countRows(){
        trCount = $('.qsm-user-behaviour-page tbody tr:visible').length;
        $('.qsm-user-behaviour-page .user-behaviour-records span').html(trCount);
    }
}(jQuery));