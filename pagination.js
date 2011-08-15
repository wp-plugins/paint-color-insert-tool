function Pagination(){
	this.total_rows = 0;
	this.per_page = 20
	this.current_page = 0;
	this.links = '';
	this.pageLinkClass = '.page-numbers';
	this.wrap = '<div class="tablenav"><div class="tablenav-pages">';
	this.wrap_trail = '</div></div>';


	this.init = function(total_rows,current_page){
		this.span = 3;
		this.total_rows = total_rows;
		this.current_page = typeof(current_page) != 'undefined' ? current_page : 1;
		this.current_page = parseInt(this.current_page, 10);
	};

	this.create_links = function(){
		this.links = '';
		var pages = Math.ceil(this.total_rows / this.per_page);

		// Not enough information to paginate
		if(pages == 1)
			return this.links;

		var i;
		if(this.current_page - this.span > 0)
			i = this.current_page - this.span;
		else
			i = this.current_page;


		for(this.offset=i;this.offset<=pages;this.offset++){
			this.links = this.links + this.get_link(pages);
		}

		return this.wrap + this.get_prev_link(pages) + this.links + this.get_next_link(pages) + this.wrap_trail;
	};

	this.get_prev_link = function(pages){
		var rel = this.current_page-1;
		if(this.current_page > 1)
			return '<a href="?from='+((this.current_page-2)*this.per_page)+'&step='+ this.per_page +'" rel="'+ rel +'" class="prev page-numbers">«</a>';
		else
			return '';
	};

	this.get_next_link = function(pages){
		var rel = this.current_page+1;
		if(this.current_page < pages)
			return '<a href="?from='+this.current_page*this.per_page+'&step='+ this.per_page +'" rel="'+ rel +'" class="next page-numbers">»</a>';
		else
			return '';

	};

	this.get_link = function(pages){

		if(this.current_page == this.offset){
			return '<span class="page-numbers current">'+ this.current_page +'</span>';
		}
		else if(this.offset > (this.current_page + this.span)){
			this.offset = this.pages - 1;
			return '<span class="page-numbers dots">...</span>';
		}else {
			return '<a href="?from='+((this.offset-1)*this.per_page)+'&step='+ this.per_page +'" rel="'+ this.offset +'" class="page-numbers">'+ this.offset +'</a>';
		}
	};

	this.url_param = function(name,url){
		var results = new RegExp('[\\?&]' + name + '=([^&#]*)').exec(url.toString());
		return results[1] || 0;
	};

	this.getPageLinkClass = function(){
		return this.pageLinkClass;
	};

}