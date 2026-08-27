var InfiniteScrollerRetrieving = false;
var AllowInfiniteScrollerRetrieving = true;

var InfiniteScroller = Class.create({
    
    initialize: function(){
        
        
        
    },
    
    getNextPosts: function(){
        
        if(InfiniteScrollerRetrieving || !AllowInfiniteScrollerRetrieving){
            
            // alert('already retrieving');
            
        }else{
            
            InfiniteScrollerRetrieving = true;
            var mostRecentPostId = this.getMostRecentPostId();
            
            new Ajax.Updater('blog-posts', '/ajax:ws/getNextBlogPostsForInfiniteScroll', {
                
                insertion: 'bottom',
                evalScripts: true,
                onSuccess: function(response) {
                    InfiniteScrollerRetrieving = false;
                    if(response.responseText.length == 0){
                        AllowInfiniteScrollerRetrieving = false;
                        $('no-more-posts').appear();
                    }
                },
                parameters: {last_post_id: mostRecentPostId}
                
            });
            
        }
        
    },
    
    getMostRecentPostId: function(){
        
        var pid = 0;
        
        $$('#blog-posts div.blog-post').each(function(el){
            
            pid = el.readAttribute('data-id');
            
        });
        
        return pid;
        
    }
    
});