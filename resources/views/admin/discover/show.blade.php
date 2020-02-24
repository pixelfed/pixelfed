@extends('admin.partial.template-full')

@section('section')
<div class="title d-flex justify-content-between align-items-center">
	<div>
		<h3 class="font-weight-bold d-inline-block">Discover Category</h3>
		<p class="lead mb-0">ID: #{{$category->id}}</p>
	</div>
	<div>
		<a class="btn btn-outline-primary btn-sm py-1" href="{{route('admin.discover')}}">Back</a>
	</div>
</div>

<hr>

<form class="px-md-5 cc-form" method="post">
	<div class="form-group row">
		<label for="categoryName" class="col-sm-2 col-form-label font-weight-bold">Name</label>
		<div class="col-sm-10">
			<input type="text" class="form-control" id="categoryName" placeholder="Nature" autocomplete="off" value="{{$category->name}}">
			<p class="form-text small font-weight-bold text-muted">Slug: /discover/c/{{$category->slug}}</p>
		</div>
	</div>
	<div class="form-group row">
		<label for="categoryName" class="col-sm-2 col-form-label font-weight-bold">Media ID</label>
		<div class="col-sm-10">
			<input type="text" class="form-control" id="categoryMedia" placeholder="1" autocomplete="off" value="{{$category->media_id}}">
			<p class="form-text small font-weight-bold text-muted">Media ID is used for category thumbnail image</p>
		</div>
	</div>
	<div class="form-group row">
		<label for="categoryActive" class="col-sm-2 col-form-label font-weight-bold">Active</label>
		<div class="col-sm-10">
			<div class="custom-control custom-switch pt-2">
				<input type="checkbox" class="custom-control-input" id="categoryActive" {{$category->active ?'checked=""':''}}>
				<label class="custom-control-label" for="categoryActive"></label>
			</div>
		</div>
	</div>

	<div class="form-group row">
		<label class="col-sm-2 col-form-label font-weight-bold">Rules</label>
		<div class="col-sm-10">
			<div class="custom-control custom-switch pt-2">
				<input type="checkbox" class="custom-control-input" id="categoryNsfw" {{!$category->no_nsfw ?'checked=""':''}}>
				<label class="custom-control-label" for="categoryNsfw">Allow NSFW</label>
			</div>
			<div class="custom-control custom-switch pt-2">
				<input type="checkbox" class="custom-control-input" id="categoryType" {{$category->photos_only ?'checked=""':''}}>
				<label class="custom-control-label" for="categoryType">Photos Only</label>
			</div>
			<div class="custom-control custom-switch pt-2">
				<input type="checkbox" class="custom-control-input" id="categoryLocal" {{$category->local_only ?'checked=""':''}}>
				<label class="custom-control-label" for="categoryType">Local Posts Only</label>
			</div>
		</div>
	</div>
	<hr>
	<div class="form-group row">
		<label for="categoryName" class="col-sm-2 col-form-label font-weight-bold">Hashtags</label>
		<div class="col-sm-10">
			<input type="text" class="form-control" id="categoryTags" data-role="tagsinput" value="{{$category->hashtags()->pluck('slug')->implode(',')}}">
		</div>
	</div>
	<div class="form-group">
		<div class="text-right">
			<button type="submit" class="btn btn-primary btn-sm py-1 font-weight-bold">Update</button>
		</div>
	</div>
</form>

@endsection

@push('scripts')
<script type="text/javascript">
	(function($){"use strict";var defaultOptions={tagClass:function(item){return'badge badge-info'},focusClass:'focus',itemValue:function(item){return item?item.toString():item},itemText:function(item){return this.itemValue(item)},itemTitle:function(item){return null},freeInput:!0,addOnBlur:!0,maxTags:undefined,maxChars:undefined,confirmKeys:[13,44],delimiter:',',delimiterRegex:null,cancelConfirmKeysOnEmpty:!1,onTagExists:function(item,$tag){$tag.addClass('sr-only')},trimValue:!1,allowDuplicates:!1,triggerChange:!0};function TagsInput(element,options){this.isInit=!0;this.itemsArray=[];this.$element=$(element);this.$element.addClass('sr-only');this.isSelect=(element.tagName==='SELECT');this.multiple=(this.isSelect&&element.hasAttribute('multiple'));this.objectItems=options&&options.itemValue;this.placeholderText=element.hasAttribute('placeholder')?this.$element.attr('placeholder'):'';this.inputSize=Math.max(1,this.placeholderText.length);this.$container=$('<div class="bootstrap-tagsinput"></div>');this.$input=$('<input type="text" placeholder="'+this.placeholderText+'"/>').appendTo(this.$container);this.$element.before(this.$container);this.build(options);this.isInit=!1}
	TagsInput.prototype={constructor:TagsInput,add:function(item,dontPushVal,options){var self=this;if(self.options.maxTags&&self.itemsArray.length>=self.options.maxTags)
	return;if(item!==!1&&!item)
	return;if(typeof item==="string"&&self.options.trimValue){item=$.trim(item)}
	if(typeof item==="object"&&!self.objectItems)
	throw("Can't add objects when itemValue option is not set");if(item.toString().match(/^\s*$/))
	return;if(self.isSelect&&!self.multiple&&self.itemsArray.length>0)
	self.remove(self.itemsArray[0]);if(typeof item==="string"&&this.$element[0].tagName==='INPUT'){var delimiter=(self.options.delimiterRegex)?self.options.delimiterRegex:self.options.delimiter;var items=item.split(delimiter);if(items.length>1){for(var i=0;i<items.length;i++){this.add(items[i],!0)}
	if(!dontPushVal)
	self.pushVal(self.options.triggerChange);return}}
	var itemValue=self.options.itemValue(item),itemText=self.options.itemText(item),tagClass=self.options.tagClass(item),itemTitle=self.options.itemTitle(item);var existing=$.grep(self.itemsArray,function(item){return self.options.itemValue(item)===itemValue})[0];if(existing&&!self.options.allowDuplicates){if(self.options.onTagExists){var $existingTag=$(".badge",self.$container).filter(function(){return $(this).data("item")===existing});self.options.onTagExists(item,$existingTag)}
	return}
	if(self.items().toString().length+item.length+1>self.options.maxInputLength)
	return;var beforeItemAddEvent=$.Event('beforeItemAdd',{item:item,cancel:!1,options:options});self.$element.trigger(beforeItemAddEvent);if(beforeItemAddEvent.cancel)
	return;self.itemsArray.push(item);var $tag=$('<span class="badge '+htmlEncode(tagClass)+(itemTitle!==null?('" title="'+itemTitle):'')+'">'+htmlEncode(itemText)+'<span data-role="remove"></span></span>');$tag.data('item',item);self.findInputWrapper().before($tag);$tag.after(' ');var optionExists=($('option[value="'+encodeURIComponent(itemValue)+'"]',self.$element).length||$('option[value="'+htmlEncode(itemValue)+'"]',self.$element).length);if(self.isSelect&&!optionExists){var $option=$('<option selected>'+htmlEncode(itemText)+'</option>');$option.data('item',item);$option.attr('value',itemValue);self.$element.append($option)}
	if(!dontPushVal)
	self.pushVal(self.options.triggerChange);if(self.options.maxTags===self.itemsArray.length||self.items().toString().length===self.options.maxInputLength)
	self.$container.addClass('bootstrap-tagsinput-max');if($('.typeahead, .twitter-typeahead',self.$container).length){self.$input.typeahead('val','')}
	if(this.isInit){self.$element.trigger($.Event('itemAddedOnInit',{item:item,options:options}))}else{self.$element.trigger($.Event('itemAdded',{item:item,options:options}))}},remove:function(item,dontPushVal,options){var self=this;if(self.objectItems){if(typeof item==="object")
	item=$.grep(self.itemsArray,function(other){return self.options.itemValue(other)==self.options.itemValue(item)});else item=$.grep(self.itemsArray,function(other){return self.options.itemValue(other)==item});item=item[item.length-1]}
	if(item){var beforeItemRemoveEvent=$.Event('beforeItemRemove',{item:item,cancel:!1,options:options});self.$element.trigger(beforeItemRemoveEvent);if(beforeItemRemoveEvent.cancel)
	return;$('.badge',self.$container).filter(function(){return $(this).data('item')===item}).remove();$('option',self.$element).filter(function(){return $(this).data('item')===item}).remove();if($.inArray(item,self.itemsArray)!==-1)
	self.itemsArray.splice($.inArray(item,self.itemsArray),1)}
	if(!dontPushVal)
	self.pushVal(self.options.triggerChange);if(self.options.maxTags>self.itemsArray.length)
	self.$container.removeClass('bootstrap-tagsinput-max');self.$element.trigger($.Event('itemRemoved',{item:item,options:options}))},removeAll:function(){var self=this;$('.badge',self.$container).remove();$('option',self.$element).remove();while(self.itemsArray.length>0)
	self.itemsArray.pop();self.pushVal(self.options.triggerChange)},refresh:function(){var self=this;$('.badge',self.$container).each(function(){var $tag=$(this),item=$tag.data('item'),itemValue=self.options.itemValue(item),itemText=self.options.itemText(item),tagClass=self.options.tagClass(item);$tag.attr('class',null);$tag.addClass('badge '+htmlEncode(tagClass));$tag.contents().filter(function(){return this.nodeType==3})[0].nodeValue=htmlEncode(itemText);if(self.isSelect){var option=$('option',self.$element).filter(function(){return $(this).data('item')===item});option.attr('value',itemValue)}})},items:function(){return this.itemsArray},pushVal:function(){var self=this,val=$.map(self.items(),function(item){return self.options.itemValue(item).toString()});self.$element.val(val.join(self.options.delimiter));if(self.options.triggerChange)
	self.$element.trigger('change')},build:function(options){var self=this;self.options=$.extend({},defaultOptions,options);if(self.objectItems)
	self.options.freeInput=!1;makeOptionItemFunction(self.options,'itemValue');makeOptionItemFunction(self.options,'itemText');makeOptionFunction(self.options,'tagClass');if(self.options.typeahead){var typeahead=self.options.typeahead||{};makeOptionFunction(typeahead,'source');self.$input.typeahead($.extend({},typeahead,{source:function(query,process){function processItems(items){var texts=[];for(var i=0;i<items.length;i++){var text=self.options.itemText(items[i]);map[text]=items[i];texts.push(text)}
	process(texts)}
	this.map={};var map=this.map,data=typeahead.source(query);if($.isFunction(data.success)){data.success(processItems)}else if($.isFunction(data.then)){data.then(processItems)}else{$.when(data).then(processItems)}},updater:function(text){self.add(this.map[text]);return this.map[text]},matcher:function(text){return(text.toLowerCase().indexOf(this.query.trim().toLowerCase())!==-1)},sorter:function(texts){return texts.sort()},highlighter:function(text){var regex=new RegExp('('+this.query+')','gi');return text.replace(regex,"<strong>$1</strong>")}}))}
	if(self.options.typeaheadjs){var typeaheadjs=self.options.typeaheadjs;if(!$.isArray(typeaheadjs)){typeaheadjs=[null,typeaheadjs]}
	$.fn.typeahead.apply(self.$input,typeaheadjs).on('typeahead:selected',$.proxy(function(obj,datum,name){var index=0;typeaheadjs.some(function(dataset,_index){if(dataset.name===name){index=_index;return!0}
	return!1});if(typeaheadjs[index].valueKey){self.add(datum[typeaheadjs[index].valueKey])}else{self.add(datum)}
	self.$input.typeahead('val','')},self))}
	self.$container.on('click',$.proxy(function(event){if(!self.$element.attr('disabled')){self.$input.removeAttr('disabled')}
	self.$input.focus()},self));if(self.options.addOnBlur&&self.options.freeInput){self.$input.on('focusout',$.proxy(function(event){if($('.typeahead, .twitter-typeahead',self.$container).length===0){self.add(self.$input.val());self.$input.val('')}},self))}
	self.$container.on({focusin:function(){self.$container.addClass(self.options.focusClass)},focusout:function(){self.$container.removeClass(self.options.focusClass)},});self.$container.on('keydown','input',$.proxy(function(event){var $input=$(event.target),$inputWrapper=self.findInputWrapper();if(self.$element.attr('disabled')){self.$input.attr('disabled','disabled');return}
	switch(event.which){case 8:if(doGetCaretPosition($input[0])===0){var prev=$inputWrapper.prev();if(prev.length){self.remove(prev.data('item'))}}
	break;case 46:if(doGetCaretPosition($input[0])===0){var next=$inputWrapper.next();if(next.length){self.remove(next.data('item'))}}
	break;case 37:var $prevTag=$inputWrapper.prev();if($input.val().length===0&&$prevTag[0]){$prevTag.before($inputWrapper);$input.focus()}
	break;case 39:var $nextTag=$inputWrapper.next();if($input.val().length===0&&$nextTag[0]){$nextTag.after($inputWrapper);$input.focus()}
	break;default:}
	var textLength=$input.val().length,wordSpace=Math.ceil(textLength/5),size=textLength+wordSpace+1;$input.attr('size',Math.max(this.inputSize,size))},self));self.$container.on('keypress','input',$.proxy(function(event){var $input=$(event.target);if(self.$element.attr('disabled')){self.$input.attr('disabled','disabled');return}
	var text=$input.val(),maxLengthReached=self.options.maxChars&&text.length>=self.options.maxChars;if(self.options.freeInput&&(keyCombinationInList(event,self.options.confirmKeys)||maxLengthReached)){if(text.length!==0){self.add(maxLengthReached?text.substr(0,self.options.maxChars):text);$input.val('')}
	if(self.options.cancelConfirmKeysOnEmpty===!1){event.preventDefault()}}
	var textLength=$input.val().length,wordSpace=Math.ceil(textLength/5),size=textLength+wordSpace+1;$input.attr('size',Math.max(this.inputSize,size))},self));self.$container.on('click','[data-role=remove]',$.proxy(function(event){if(self.$element.attr('disabled')){return}
	self.remove($(event.target).closest('.badge').data('item'))},self));if(self.options.itemValue===defaultOptions.itemValue){if(self.$element[0].tagName==='INPUT'){self.add(self.$element.val())}else{$('option',self.$element).each(function(){self.add($(this).attr('value'),!0)})}}},destroy:function(){var self=this;self.$container.off('keypress','input');self.$container.off('click','[role=remove]');self.$container.remove();self.$element.removeData('tagsinput');self.$element.show()},focus:function(){this.$input.focus()},input:function(){return this.$input},findInputWrapper:function(){var elt=this.$input[0],container=this.$container[0];while(elt&&elt.parentNode!==container)
	elt=elt.parentNode;return $(elt)}};$.fn.tagsinput=function(arg1,arg2,arg3){var results=[];this.each(function(){var tagsinput=$(this).data('tagsinput');if(!tagsinput){tagsinput=new TagsInput(this,arg1);$(this).data('tagsinput',tagsinput);results.push(tagsinput);if(this.tagName==='SELECT'){$('option',$(this)).attr('selected','selected')}
	$(this).val($(this).val())}else if(!arg1&&!arg2){results.push(tagsinput)}else if(tagsinput[arg1]!==undefined){if(tagsinput[arg1].length===3&&arg3!==undefined){var retVal=tagsinput[arg1](arg2,null,arg3)}else{var retVal=tagsinput[arg1](arg2)}
	if(retVal!==undefined)
	results.push(retVal)}});if(typeof arg1=='string'){return results.length>1?results:results[0]}else{return results}};$.fn.tagsinput.Constructor=TagsInput;function makeOptionItemFunction(options,key){if(typeof options[key]!=='function'){var propertyName=options[key];options[key]=function(item){return item[propertyName]}}}
	function makeOptionFunction(options,key){if(typeof options[key]!=='function'){var value=options[key];options[key]=function(){return value}}}
	var htmlEncodeContainer=$('<div />');function htmlEncode(value){if(value){return htmlEncodeContainer.text(value).html()}else{return''}}
	function doGetCaretPosition(oField){var iCaretPos=0;if(document.selection){oField.focus();var oSel=document.selection.createRange();oSel.moveStart('character',-oField.value.length);iCaretPos=oSel.text.length}else if(oField.selectionStart||oField.selectionStart=='0'){iCaretPos=oField.selectionStart}
	return(iCaretPos)}
	function keyCombinationInList(keyPressEvent,lookupList){var found=!1;$.each(lookupList,function(index,keyCombination){if(typeof(keyCombination)==='number'&&keyPressEvent.which===keyCombination){found=!0;return!1}
	if(keyPressEvent.which===keyCombination.which){var alt=!keyCombination.hasOwnProperty('altKey')||keyPressEvent.altKey===keyCombination.altKey,shift=!keyCombination.hasOwnProperty('shiftKey')||keyPressEvent.shiftKey===keyCombination.shiftKey,ctrl=!keyCombination.hasOwnProperty('ctrlKey')||keyPressEvent.ctrlKey===keyCombination.ctrlKey;if(alt&&shift&&ctrl){found=!0;return!1}}});return found}
	$(function(){$("input[data-role=tagsinput], select[multiple][data-role=tagsinput]").tagsinput()})})(window.jQuery)
</script>
<script type="text/javascript">
$(document).ready(function() {
	$('#categoryTags').tagsinput();

	$('#categoryTags').on('beforeItemAdd', function(event) {
		let tag = event.item;
		axios.post('{{route('admin.discover.create-hashtag')}}', {
			'category_id': {{$category->id}},
			'hashtag': tag,
			'action': 'create'
		}).catch(function(err) {
			event.cancel = true;
		});
	});

	$('#categoryTags').on('beforeItemRemove', function(event) {
		let tag = event.item;
		axios.post('{{route('admin.discover.create-hashtag')}}', {
			'category_id': {{$category->id}},
			'hashtag': tag,
			'action': 'delete'
		}).catch(function(err) {
			event.cancel = true;
		});
	});

	$('.cc-form').on('submit', function(e) {
		e.preventDefault();
		let data = {
			'name': document.getElementById('categoryName').value,
			'media': document.getElementById('categoryMedia').value,
			'active': document.getElementById('categoryActive').checked,
			'hashtags': document.getElementById('categoryTags').value
		};

		axios.post('{{request()->url()}}', data)
			.then(res => {
				window.location.href = '{{request()->url()}}';
			});
	})
});
</script>
@endpush

@push('styles')
<style type="text/css">
/*
 * bootstrap-tagsinput v0.8.0
 * 
 */

.bootstrap-tagsinput {
  background-color: #fff;
  border: 1px solid #ccc;
  box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
  display: inline-block;
  padding: 4px 6px;
  color: #555;
  vertical-align: middle;
  border-radius: 4px;
  width: 100%;
  line-height: 22px;
  cursor: text;
}
.bootstrap-tagsinput input {
  border: none;
  box-shadow: none;
  outline: none;
  background-color: transparent;
  padding: 0 6px;
  margin: 0;
  width: auto;
  max-width: inherit;
}
.bootstrap-tagsinput.form-control input::-moz-placeholder {
  color: #777;
  opacity: 1;
}
.bootstrap-tagsinput.form-control input:-ms-input-placeholder {
  color: #777;
}
.bootstrap-tagsinput.form-control input::-webkit-input-placeholder {
  color: #777;
}
.bootstrap-tagsinput input:focus {
  border: none;
  box-shadow: none;
}
.bootstrap-tagsinput .badge {
  margin-right: 2px;
  color: white;
  background-color:#0275d8;
  padding:5px 8px;border-radius:3px;
  border:1px solid #01649e
}
.bootstrap-tagsinput .badge [data-role="remove"] {
  margin-left: 8px;
  cursor: pointer;
}
.bootstrap-tagsinput .badge [data-role="remove"]:after {
  content: "×";
  padding: 0 4px;
  background-color:rgba(0, 0, 0, 0.1);
  border-radius:50%;
  font-size:13px
}
.bootstrap-tagsinput .badge [data-role="remove"]:hover:after {
  background-color:rgba(0, 0, 0, 0.62);
}
.bootstrap-tagsinput .badge [data-role="remove"]:hover:active {
  box-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
}
</style>
@endpush
