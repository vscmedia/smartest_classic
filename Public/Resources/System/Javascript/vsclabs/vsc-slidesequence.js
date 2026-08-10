VSC.SlideSequence = Class.create({
    
    defaultView: null,
    currentView: null,
    listElementPrefix: null,
    navListElementItemPrefix: null,
    
    initialize: function(options){
        
        this.listElementPrefix = options.prefix;
        this.defaultView = options.defaultView;
        this.navListElementItemPrefix = options.navListElementItemPrefix;
        
        if(window.location.hash && document.getElementById(this.listElementPrefix+'-'+this.removeHash(window.location.hash))){
            this.setView(window.location.hash);
        }else{
            this.setView(this.defaultView);
        }
        
        document.observe("hash:changed", this.setViewFromHashChangeEvent.bind(this));
        
    },
    
    hideCurrentView: function(){
        
        this.hideView(this.currentView);
        
    },
    
    hideView: function(view){
        if(document.getElementById(this.listElementPrefix+'-'+view)){
            $(this.listElementPrefix+'-'+view).fade({duration: 0.4});
            // $(this.navListElementItemPrefix+'-'+view).removeClassName('current');
            // console.log(this.navListElementItemPrefix+'-'+view);
            $$('#'+this.listElementPrefix+'-'+view+' li').each(function(s) {
                setTimeout(function(){s.style.display = 'none'}, 1000);
            });
        }
    },
    
    showView: function(view){
        if(document.getElementById(this.listElementPrefix+'-'+view)){
            $(this.listElementPrefix+'-'+view).style.display='block';
            // $(this.navListElementItemPrefix+'-'+view).addClassName('current');
            // console.log(this.navListElementItemPrefix+'-'+view);
            new VSC.Effect.DisplayProgressively('#'+this.listElementPrefix+'-'+view+' li', view, {interval: 180, duration: 0.9});
        }
    },
    
    setView: function(v){
        
        view = this.removeHash(v);
        
        if(this.currentView != view && document.getElementById(this.listElementPrefix+'-'+view)){
        
            var cv = this.currentView;
            
            if(this.currentView){
                this.hideCurrentView();
            }
    
            if(document.getElementById(this.listElementPrefix+'-'+view)){
                this.showView(view);
            }
    
            this.currentView = view;
    
        }
        
    },
    
    setViewFromHashChangeEvent: function(event){
        this.setView(event.memo.currentHash);
    },
    
    removeHash: function(hash){
        if(hash.charAt(0) == '#'){
            return hash.substring(1);
        }else{
            return hash;
        }
    }
    
});