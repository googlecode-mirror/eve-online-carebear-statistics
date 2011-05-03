"use strict";

/**
 * Copyright (c) 2011 Mikk Kiilaspää (mikk36 at mikk36 eu)

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
 */

if(typeof(window.console) === 'undefined') window.console = {log: function (){}};

if (!Date.now)
{
  Date.now = function now()
  {
    return +new Date();
  };
}

var carebear = {
  fadeSpeed: 400,
  currentPage: 'byCharacter',
  flotPoint: null,
  optionsExtraHolder: {},
  careUsername: null,
  carePassword: null,
  loaderImage: 'ajax-loader',
  pageFunction: null,
  pageArgument: null,
  dateRangePickers:
  {
    from: {},
    to: {}
  },
  historyStack: {},
  initPage: function()
  {
    if(CBconfig.inEVE)
      carebear.loaderImage += '_ui-darkness';
    $.Jookie.Initialise('Carebearing', -1);
    $('#careSubmit').button();
    $('#loginform').submit(function()
    {
      if($.Jookie.Get('Carebearing', 'username') === undefined)
      {
        carebear.careUsername = $('#careUsername').val();
        carebear.carePassword = $.md5($('#carePassword').val());
      }
      var options = {
        auth: true,
        username: carebear.careUsername,
        password: carebear.carePassword
      };
      $.ajax({
        type: 'POST',
        dataType: 'json',
        cache: false,
        data: options,
        statusCode:
        {
          403: function()
          {
            alert('Invalid auth data!');
            $.Jookie.Unset('Carebearing', 'username');
            $.Jookie.Unset('Carebearing', 'password');
            $('#careUsername').focus();
          }
        },
        success: function()
        {
          $.Jookie.Set('Carebearing', 'username', carebear.careUsername);
          $.Jookie.Set('Carebearing', 'password', carebear.carePassword);
          carebear.startPage();
        }
      });
      return false;
    });
    if($.Jookie.Get('Carebearing', 'username') !== undefined)
    {
      carebear.careUsername = $.Jookie.Get('Carebearing', 'username');
      carebear.carePassword = $.Jookie.Get('Carebearing', 'password');
      $('#loginform').submit();
    }
    else
    {
      $('#careUsername').focus();
    }
  },
  startPage: function()
  {
    $('#page').removeAttr('style').html('');
    $('#page').append('<div id="pageSelection"></div><div id="content"></div><div id="loading" class="loading"><img src="images/'+carebear.loaderImage+'.gif" width="31" height="31" /></div>');
    $('#content').append('<div id="toolbarArea"></div>');
    $('#toolbarArea').append('<div id="dateRange"></div>');
    $('#toolbarArea').append('<div id="extraButtons"></div>');
    $('#toolbarArea').append('<div class="clear"></div>');
    this.setupDateRangePicker($('#dateRange'));
    $('#content').append('<div id="outputDiv"></div>');
    $('#pageSelection').append('<input type="radio" id="pageByCharacterButton" name="pageSelection" value="ByCharacter" checked="checked" /><label for="pageByCharacterButton">'+Lang.characters+'</label>');
    $('#pageSelection').append('<input type="radio" id="pageByDayButton" name="pageSelection" value="ByDay" /><label for="pageByDayButton">'+Lang.dailyGraph+'</label>');
    $('#pageSelection').append('<input type="radio" id="pageBySystemButton" name="pageSelection" value="BySystem" /><label for="pageBySystemButton">'+Lang.systems+'</label>');
    $('#pageSelection').append('<input type="radio" id="pageByRegionButton" name="pageSelection" value="ByRegion" /><label for="pageByRegionButton">'+Lang.regions+'</label>');
    $('#pageSelection').append('<input type="radio" id="pageByHisecCharacterButton" name="pageSelection" value="ByHisecCharacter" /><label for="pageByHisecCharacterButton">'+Lang.highsecBears+'</label>');
    $('#pageSelection').append('<input type="radio" id="pageByAgentButton" name="pageSelection" value="ByAgent" /><label for="pageByAgentButton">'+Lang.agents+'</label>');
    $('#pageSelection').append('<input type="radio" id="pageByRatButton" name="pageSelection" value="ByRat" /><label for="pageByRatButton">'+Lang.rats+'</label>');
    $('#pageSelection').append('<input type="radio" id="pageByFancyRatButton" name="pageSelection" value="ByFancyRat" /><label for="pageByFancyRatButton">'+Lang.notableRats+'</label>');
    $('#pageSelection').buttonset();
    $("body").append('<div id="copyright" class="ui-widget"><span>CCP Copyright Notice</span><p>EVE Online, the EVE logo, EVE and all associated logos and designs are the intellectual property of CCP hf. All artwork, screenshots, characters, vehicles, storylines, world facts or other recognizable features of the intellectual property relating to these trademarks are likewise the intellectual property of CCP hf. EVE Online and the EVE logo are the registered trademarks of CCP hf. All rights are reserved worldwide. All other trademarks are the property of their respective owners. CCP hf. has granted permission to Mikk Kiilaspää to use EVE Online and all associated logos and designs for promotional and information purposes on its website but does not endorse, and is not in any way affiliated with, Mikk Kiilaspää. CCP is in no way responsible for the content on or functioning of this website, nor can it be liable for any damage arising from the use of this website.</p></div>');
    $("#copyright span").click(function() {
      $("#copyright p").fadeToggle('fast');
    });
    $.history.init(carebear.historyCallback);
    //carebear.pageSelectionChanged($("#pageSelection input:checked"));
    this.setEventsForPageSelection();
  },
  setEventsForPageSelection: function()
  {
    //console.log('setEventsForPageSelection');
    $("#pageSelection input[name='pageSelection']").change(function()
    {
      carebear.setHistoryData(carebear.pageSelectionChanged, this, $(this).val(), carebear.dateRangePickers.from.datepicker('getDate'), carebear.dateRangePickers.to.datepicker('getDate'));
      //carebear.pageSelectionChanged(this);
    });
  },
  setHistoryData: function(pageFunction, pageArgument, tabID, fromDate, toDate)
  {
    //console.log(Date.now()+' Setting history');
    var historyData = {
      pageFunction: pageFunction,
      pageArgument: pageArgument,
      fromDate: fromDate,
      toDate: toDate,
      tabID: tabID
    };
    var now = Date.now();
    carebear.historyStack[now] = historyData;
    //$.history(historyData);
    $.history.load(now);
  },
  historyCallback: function(hash)
  {
    //console.log('history callback');
    if(hash === "")
    {
      var curDate = new Date();
      carebear.dateRangePickers.from.datepicker('setDate', new Date(curDate.getFullYear(), curDate.getMonth(), '1'));
      curDate = new Date(Date.now() + curDate.getTimezoneOffset()*60*1000);
      carebear.dateRangePickers.to.datepicker('setDate', curDate);
      $("#pageSelection input:checked").removeAttr('checked');
      $('#pageByCharacterButton').attr('checked', true).button('refresh');
      carebear.pageSelectionChanged($("#pageSelection input:checked"));
    }
    else
    {
      var reinstate = carebear.historyStack[hash];
      if(typeof(reinstate) == "undefined")
        return;
      carebear.dateRangePickers.from.datepicker('setDate', reinstate.fromDate);
      carebear.dateRangePickers.to.datepicker('setDate', reinstate.toDate);
      if(reinstate.tabID !== null)
      {
        $("#pageSelection input:checked").removeAttr('checked');
        $("#page"+reinstate.tabID+"Button").attr('checked', true).button('refresh');
      }
      else
      {
        carebear.currentPage = null;
        $("#pageSelection input:checked").removeAttr('checked').button('refresh');
      }
      carebear.pageFunction = reinstate.pageFunction;
      carebear.pageArgument = reinstate.pageArgument;
      executeFunction(reinstate.pageFunction, reinstate.pageArgument)
    }
  },
  pageSelectionChanged: function(what)
  {
    carebear.pageFunction = carebear.pageSelectionChanged;
    carebear.pageArgument = what;
    $('#outputDiv').html('');
    $('#extraButtons').html('');
    $('#loading').show();
    carebear.currentPage = $(what).val();
    var callback = undefined;
    switch($(what).attr('id'))
    {
      case 'pageByCharacterButton':
        //console.log('By Character');
        callback = carebear.activateByCharacterPage;
        break;
      case 'pageByDayButton':
        //console.log('By System');
        callback = carebear.activateByDayPage;
        break;
      case 'pageBySystemButton':
        //console.log('By System');
        callback = carebear.activateBySystemPage;
        break;
      case 'pageByRegionButton':
        //console.log('By System');
        callback = carebear.activateByRegionPage;
        break;
      case 'pageByHisecCharacterButton':
        //console.log('By Character');
        callback = carebear.activateByHisecCharacterPage;
        break;
      case 'pageByAgentButton':
        //console.log('By System');
        callback = carebear.activateByAgentPage;
        break;
      case 'pageByRatButton':
        //console.log('By System');
        callback = carebear.activateByRatPage;
        break;
      case 'pageByFancyRatButton':
        //console.log('By System');
        callback = carebear.activateByFancyRatPage;
        break;
    }
    carebear.fetchData(callback);
  },
  setupDateRangePicker: function(context)
  {
    context.append('<label for="dateRangeFrom">'+Lang.from+' </label><input type="text" id="dateRangeFrom" name="dateRangeFrom" class="ui-widget-content" />');
    context.append('<label for="dateRangeTo"> '+Lang.to+' </label><input type="text" id="dateRangeTo" name="dateRangeTo" class="ui-widget-content" />');

    carebear.dateRangePickers.from = $("#dateRangeFrom").datepicker({
      maxDate: 'today',
      showButtonPanel: true,
      onSelect: function( selectedDate ) {
				var option = "minDate",
					instance = $( this ).data( "datepicker" ),
					date = $.datepicker.parseDate(
						instance.settings.dateFormat ||
						$.datepicker._defaults.dateFormat,
						selectedDate, instance.settings );
				carebear.dateRangePickers.to.datepicker( "option", option, date );
        carebear.setHistoryData(carebear.pageFunction, carebear.pageArgument, carebear.currentPage, carebear.dateRangePickers.from.datepicker('getDate'), carebear.dateRangePickers.to.datepicker('getDate'));
        executeFunction(carebear.pageFunction, carebear.pageArgument);
			}
    });
    var curDate = new Date();
    carebear.dateRangePickers.from.datepicker('setDate', new Date(curDate.getFullYear(), curDate.getMonth(), '1'));

    carebear.dateRangePickers.to = $("#dateRangeTo").datepicker({
      minDate: '-1m',
      showButtonPanel: true,
      onSelect: function( selectedDate ) {
				var option = "maxDate",
					instance = $( this ).data( "datepicker" ),
					date = $.datepicker.parseDate(
						instance.settings.dateFormat ||
						$.datepicker._defaults.dateFormat,
						selectedDate, instance.settings );
				carebear.dateRangePickers.from.datepicker( "option", option, date );
        carebear.setHistoryData(carebear.pageFunction, carebear.pageArgument, carebear.currentPage, carebear.dateRangePickers.from.datepicker('getDate'), carebear.dateRangePickers.to.datepicker('getDate'));
        executeFunction(carebear.pageFunction, carebear.pageArgument);
			}
    });
    curDate = new Date(Date.now() + curDate.getTimezoneOffset()*60*1000);
    carebear.dateRangePickers.to.datepicker('setDate', curDate);
  },
  activateByCharacterPage: function(data)
  {
    //console.log(Date.now()+' byCharacter displaying');
    carebear.optionsExtraHolder = {};
    $("#loading").hide();
    $("#outputDiv").html('<table id="dataTable" class="display"></table>');
    $("#extraButtons").html(Lang.total+' '+$().number_format(data.totalSum, {numberOfDecimals: 0, thousandSeparator: Lang.thousandSeparator, decimalSeparator: Lang.decimalSeparator})+" ISK");
    data.aoColumns[1].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getPlayerDetailsPage({id: '+oObj.aData[2]+', name: \''+htmlentities(oObj.aData[1], 'ENT_QUOTES')+'\'})">'+oObj.aData[1]+'</span>';
    };
    $('#dataTable').dataTable(data);
  },
  getPlayerDetailsPage: function(character)
  {
    //console.log(Date.now()+' getPlayerDetailsPage');
    carebear.setHistoryData(carebear.getPlayerDetailsPage2, character, null, carebear.dateRangePickers.from.datepicker('getDate'), carebear.dateRangePickers.to.datepicker('getDate'));
    //carebear.getPlayerDetailsPage2(character);
  },
  getPlayerDetailsPage2: function(character)
  {
    //console.log(Date.now()+' getPlayerDetailsPage2');
    carebear.currentPage = null;
    carebear.pageFunction = carebear.getPlayerDetailsPage2;
    carebear.pageArgument = character;
    //console.log('Get player '+character.id);
    $('#outputDiv').html('');
    $('#extraButtons').html(character.name+', <span class="total"></span>');
    $("#pageSelection input:checked").removeAttr('checked').button('refresh');
    $('#outputDiv').append('<p class="detailsHeader">'+Lang.timeline+'</p><div id="playerDetailsTimeline"><div class="loading" style="display: block;"><img src="images/'+carebear.loaderImage+'.gif" width="31" height="31" /></div></div>');
    carebear.fetchData(carebear.showPlayerDetailsTimeline, {act: 'ByDay', character: character.id});
    $('#outputDiv').append('<p class="detailsHeader">'+Lang.systems+'</p><div id="playerDetailsSystems"><div class="loading" style="display: block;"><img src="images/'+carebear.loaderImage+'.gif" width="31" height="31" /></div></div>');
    carebear.fetchData(carebear.showPlayerDetailsSystems, {act: 'BySystem', character: character.id});
    $('#outputDiv').append('<p class="detailsHeader">'+Lang.agents+'</p><div id="playerDetailsAgents"><div class="loading" style="display: block;"><img src="images/'+carebear.loaderImage+'.gif" width="31" height="31" /></div></div>');
    carebear.fetchData(carebear.showPlayerDetailsAgents, {act: 'ByAgent', character: character.id});
    $('#outputDiv').append('<p class="detailsHeader">'+Lang.rats+'</p><div id="playerDetailsRats"><div class="loading" style="display: block;"><img src="images/'+carebear.loaderImage+'.gif" width="31" height="31" /></div></div>');
    carebear.fetchData(carebear.showPlayerDetailsRats, {act: 'ByRat', character: character.id});
  },
  showPlayerDetailsTimeline: function(data)
  {
    $("#extraButtons .total").text($().number_format(data.totalSum, {numberOfDecimals: 0, thousandSeparator: Lang.thousandSeparator, decimalSeparator: Lang.decimalSeparator}) + ' ISK')
    carebear.showGraph($('#playerDetailsTimeline'), data, true);
  },
  showPlayerDetailsSystems: function(data)
  {
    data.iDisplayLength = 10;
    data.aoColumns[1].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getSystemDetailsPage({id: '+oObj.aData[2]+', name: \''+htmlentities(oObj.aData[1], 'ENT_QUOTES')+'\'})">'+oObj.aData[1]+'</span>';
    };
    data.aoColumns[4].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getRegionDetailsPage({id: '+oObj.aData[5]+', name: \''+htmlentities(oObj.aData[4], 'ENT_QUOTES')+'\'})">'+oObj.aData[4]+'</span>';
    };
    $('#playerDetailsSystems').html('<table class="display"></table>');
    $('#playerDetailsSystems table').dataTable(data);
  },
  showPlayerDetailsAgents: function(data)
  {
    data.iDisplayLength = 10;
    $('#playerDetailsAgents').html('<table class="display"></table>');
    data.aoColumns[1].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getAgentDetailsPage({id: '+oObj.aData[2]+', name: \''+htmlentities(oObj.aData[1], 'ENT_QUOTES')+'\'})">'+oObj.aData[1]+'</span>';
    };
    $('#playerDetailsAgents table').dataTable(data);
  },
  showPlayerDetailsRats: function(data)
  {
    data.iDisplayLength = 10;
    $('#playerDetailsRats').html('<table class="display"></table>');
    data.aoColumns[0].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getRatDetailsPage({id: '+oObj.aData[1]+', name: \''+htmlentities(oObj.aData[0], 'ENT_QUOTES')+'\'})">'+oObj.aData[0]+'</span>';
    };
    $('#playerDetailsRats table').dataTable(data);
  },
  activateByDayPage: function(data)
  {
    $('#loading').hide();
    $("#extraButtons").html(Lang.total+' '+$().number_format(data.totalSum, {numberOfDecimals: 0, thousandSeparator: Lang.thousandSeparator, decimalSeparator: Lang.decimalSeparator})+" ISK");
    if(carebear.optionsExtraHolder.character !== undefined)
    {
      //console.log('found');
      $("#extraButtons").html(carebear.optionsExtraHolder.character);
    }
    carebear.showGraph($('#outputDiv'), data);
  },
  activateBySystemPage: function(data)
  {
    carebear.optionsExtraHolder = {};
    $('#loading').hide();
    $('#outputDiv').html('<table id="dataTable" class="display"></table>');
    $("#extraButtons").html(Lang.total+' '+$().number_format(data.totalSum, {numberOfDecimals: 0, thousandSeparator: Lang.thousandSeparator, decimalSeparator: Lang.decimalSeparator})+" ISK");
    data.aoColumns[1].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getSystemDetailsPage({id: '+oObj.aData[2]+', name: \''+htmlentities(oObj.aData[1], 'ENT_QUOTES')+'\'})">'+oObj.aData[1]+'</span>';
    };
    data.aoColumns[4].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getRegionDetailsPage({id: '+oObj.aData[5]+', name: \''+htmlentities(oObj.aData[4], 'ENT_QUOTES')+'\'})">'+oObj.aData[4]+'</span>';
    };
    $('#dataTable').dataTable(data);
  },
  getSystemDetailsPage: function(system)
  {
    carebear.setHistoryData(carebear.getSystemDetailsPage2, system, null, carebear.dateRangePickers.from.datepicker('getDate'), carebear.dateRangePickers.to.datepicker('getDate'));
    //carebear.getSystemDetailsPage2(system);
  },
  getSystemDetailsPage2: function(system)
  {
    carebear.currentPage = null;
    carebear.pageFunction = carebear.getSystemDetailsPage2;
    carebear.pageArgument = system;
    //console.log('Get system '+system.id);
    $('#outputDiv').html('');
    $('#extraButtons').html(system.name+', <span class="total"></span>');
    $("#pageSelection input:checked").removeAttr('checked').button('refresh');
    $('#outputDiv').append('<p class="detailsHeader">'+Lang.timeline+'</p><div id="systemDetailsTimeline"><div class="loading" style="display: block;"><img src="images/'+carebear.loaderImage+'.gif" width="31" height="31" /></div></div>');
    carebear.fetchData(carebear.showSystemDetailsTimeline, {act: 'ByDay', system: system.id});
    $('#outputDiv').append('<p class="detailsHeader">'+Lang.characters+'</p><div id="systemDetailsPlayers"><div class="loading" style="display: block;"><img src="images/'+carebear.loaderImage+'.gif" width="31" height="31" /></div></div>');
    carebear.fetchData(carebear.showSystemDetailsPlayers, {act: 'ByCharacter', system: system.id});
    $('#outputDiv').append('<p class="detailsHeader">'+Lang.agents+'</p><div id="systemDetailsAgents"><div class="loading" style="display: block;"><img src="images/'+carebear.loaderImage+'.gif" width="31" height="31" /></div></div>');
    carebear.fetchData(carebear.showSystemDetailsAgents, {act: 'ByAgent', system: system.id});
    $('#outputDiv').append('<p class="detailsHeader">'+Lang.rats+'</p><div id="systemDetailsRats"><div class="loading" style="display: block;"><img src="images/'+carebear.loaderImage+'.gif" width="31" height="31" /></div></div>');
    carebear.fetchData(carebear.showSystemDetailsRats, {act: 'ByRat', system: system.id});
  },
  showSystemDetailsTimeline: function(data)
  {
    $("#extraButtons .total").text($().number_format(data.totalSum, {numberOfDecimals: 0, thousandSeparator: Lang.thousandSeparator, decimalSeparator: Lang.decimalSeparator}) + ' ISK')
    carebear.showGraph($('#systemDetailsTimeline'), data, true);
  },
  showSystemDetailsPlayers: function(data)
  {
    data.iDisplayLength = 10;
    data.aoColumns[1].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getPlayerDetailsPage({id: '+oObj.aData[2]+', name: \''+htmlentities(oObj.aData[1], 'ENT_QUOTES')+'\'})">'+oObj.aData[1]+'</span>';
    };
    $('#systemDetailsPlayers').html('<table class="display"></table>');
    $('#systemDetailsPlayers table').dataTable(data);
  },
  showSystemDetailsAgents: function(data)
  {
    data.iDisplayLength = 10;
    $('#systemDetailsAgents').html('<table class="display"></table>');
    data.aoColumns[1].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getAgentDetailsPage({id: '+oObj.aData[2]+', name: \''+htmlentities(oObj.aData[1], 'ENT_QUOTES')+'\'})">'+oObj.aData[1]+'</span>';
    };
    $('#systemDetailsAgents table').dataTable(data);
  },
  showSystemDetailsRats: function(data)
  {
    data.iDisplayLength = 10;
    $('#systemDetailsRats').html('<table class="display"></table>');
    data.aoColumns[0].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getRatDetailsPage({id: '+oObj.aData[1]+', name: \''+htmlentities(oObj.aData[0], 'ENT_QUOTES')+'\'})">'+oObj.aData[0]+'</span>';
    };
    $('#systemDetailsRats table').dataTable(data);
  },
  activateByRegionPage: function(data)
  {
    carebear.optionsExtraHolder = {};
    $('#loading').hide();
    $('#outputDiv').html('<table id="dataTable" class="display"></table>');
    $("#extraButtons").html(Lang.total+' '+$().number_format(data.totalSum, {numberOfDecimals: 0, thousandSeparator: Lang.thousandSeparator, decimalSeparator: Lang.decimalSeparator})+" ISK");
    data.aoColumns[1].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getRegionDetailsPage({id: '+oObj.aData[2]+', name: \''+htmlentities(oObj.aData[1], 'ENT_QUOTES')+'\'})">'+oObj.aData[1]+'</span>';
    };
    $('#dataTable').dataTable(data);
  },
  getRegionDetailsPage: function(region)
  {
    carebear.setHistoryData(carebear.getRegionDetailsPage2, region, null, carebear.dateRangePickers.from.datepicker('getDate'), carebear.dateRangePickers.to.datepicker('getDate'));
    //carebear.getRegionDetailsPage2(region);
  },
  getRegionDetailsPage2: function(region)
  {
    carebear.currentPage = null;
    carebear.pageFunction = carebear.getRegionDetailsPage2;
    carebear.pageArgument = region;
    //console.log('Get region '+region.id);
    $('#outputDiv').html('');
    $('#extraButtons').html(region.name+', <span class="total"></span>');
    $("#pageSelection input:checked").removeAttr('checked').button('refresh');
    $('#outputDiv').append('<p class="detailsHeader">'+Lang.timeline+'</p><div id="regionDetailsTimeline"><div class="loading" style="display: block;"><img src="images/'+carebear.loaderImage+'.gif" width="31" height="31" /></div></div>');
    carebear.fetchData(carebear.showRegionDetailsTimeline, {act: 'ByDay', region: region.id});
    $('#outputDiv').append('<p class="detailsHeader">'+Lang.characters+'</p><div id="regionDetailsPlayers"><div class="loading" style="display: block;"><img src="images/'+carebear.loaderImage+'.gif" width="31" height="31" /></div></div>');
    carebear.fetchData(carebear.showRegionDetailsPlayers, {act: 'ByCharacter', region: region.id});
    $('#outputDiv').append('<p class="detailsHeader">'+Lang.systems+'</p><div id="regionDetailsSystems"><div class="loading" style="display: block;"><img src="images/'+carebear.loaderImage+'.gif" width="31" height="31" /></div></div>');
    carebear.fetchData(carebear.showRegionDetailsSystems, {act: 'BySystem', region: region.id});
    $('#outputDiv').append('<p class="detailsHeader">'+Lang.agents+'</p><div id="regionDetailsAgents"><div class="loading" style="display: block;"><img src="images/'+carebear.loaderImage+'.gif" width="31" height="31" /></div></div>');
    carebear.fetchData(carebear.showRegionDetailsAgents, {act: 'ByAgent', region: region.id});
    $('#outputDiv').append('<p class="detailsHeader">'+Lang.rats+'</p><div id="regionDetailsRats"><div class="loading" style="display: block;"><img src="images/'+carebear.loaderImage+'.gif" width="31" height="31" /></div></div>');
    carebear.fetchData(carebear.showRegionDetailsRats, {act: 'ByRat', region: region.id});
  },
  showRegionDetailsTimeline: function(data)
  {
    $("#extraButtons .total").text($().number_format(data.totalSum, {numberOfDecimals: 0, thousandSeparator: Lang.thousandSeparator, decimalSeparator: Lang.decimalSeparator}) + ' ISK')
    carebear.showGraph($('#regionDetailsTimeline'), data, true);
  },
  showRegionDetailsPlayers: function(data)
  {
    data.iDisplayLength = 10;
    data.aoColumns[1].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getPlayerDetailsPage({id: '+oObj.aData[2]+', name: \''+htmlentities(oObj.aData[1], 'ENT_QUOTES')+'\'})">'+oObj.aData[1]+'</span>';
    };
    $('#regionDetailsPlayers').html('<table class="display"></table>');
    $('#regionDetailsPlayers table').dataTable(data);
  },
  showRegionDetailsSystems: function(data)
  {
    data.iDisplayLength = 10;
    data.aoColumns[1].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getSystemDetailsPage({id: '+oObj.aData[2]+', name: \''+htmlentities(oObj.aData[1], 'ENT_QUOTES')+'\'})">'+oObj.aData[1]+'</span>';
    };
    data.aoColumns[4].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getRegionDetailsPage({id: '+oObj.aData[5]+', name: \''+htmlentities(oObj.aData[4], 'ENT_QUOTES')+'\'})">'+oObj.aData[4]+'</span>';
    };
    $('#regionDetailsSystems').html('<table class="display"></table>');
    $('#regionDetailsSystems table').dataTable(data);
  },
  showRegionDetailsAgents: function(data)
  {
    data.iDisplayLength = 10;
    $('#regionDetailsAgents').html('<table class="display"></table>');
    data.aoColumns[1].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getAgentDetailsPage({id: '+oObj.aData[2]+', name: \''+htmlentities(oObj.aData[1], 'ENT_QUOTES')+'\'})">'+oObj.aData[1]+'</span>';
    };
    $('#regionDetailsAgents table').dataTable(data);
  },
  showRegionDetailsRats: function(data)
  {
    data.iDisplayLength = 10;
    $('#regionDetailsRats').html('<table class="display"></table>');
    data.aoColumns[0].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getRatDetailsPage({id: '+oObj.aData[1]+', name: \''+htmlentities(oObj.aData[0], 'ENT_QUOTES')+'\'})">'+oObj.aData[0]+'</span>';
    };
    $('#regionDetailsRats table').dataTable(data);
  },
  activateByHisecCharacterPage: function(data)
  {
    carebear.optionsExtraHolder = {};
    $('#loading').hide();
    $('#outputDiv').html('<table id="dataTable" class="display"></table>');
    $("#extraButtons").html(Lang.total+' '+$().number_format(data.totalSum, {numberOfDecimals: 0, thousandSeparator: Lang.thousandSeparator, decimalSeparator: Lang.decimalSeparator})+" ISK");
    data.aoColumns[1].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getPlayerDetailsPage({id: '+oObj.aData[2]+', name: \''+htmlentities(oObj.aData[1], 'ENT_QUOTES')+'\'})">'+oObj.aData[1]+'</span>';
    };
    $('#dataTable').dataTable(data);
  },
  activateByAgentPage: function(data)
  {
    carebear.optionsExtraHolder = {};
    $('#loading').hide();
    $('#outputDiv').html('<table id="dataTable" class="display"></table>');
    $("#extraButtons").html(Lang.total+' '+$().number_format(data.totalSum, {numberOfDecimals: 0, thousandSeparator: Lang.thousandSeparator, decimalSeparator: Lang.decimalSeparator})+" ISK");
    data.aoColumns[1].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getAgentDetailsPage({id: '+oObj.aData[2]+', name: \''+htmlentities(oObj.aData[1], 'ENT_QUOTES')+'\'})">'+oObj.aData[1]+'</span>';
    };
    $('#dataTable').dataTable(data);
  },
  getAgentDetailsPage: function(agent)
  {
    carebear.setHistoryData(carebear.getAgentDetailsPage2, agent, null, carebear.dateRangePickers.from.datepicker('getDate'), carebear.dateRangePickers.to.datepicker('getDate'));
    //carebear.getAgentDetailsPage2(agent);
  },
  getAgentDetailsPage2: function(agent)
  {
    carebear.currentPage = null;
    carebear.pageFunction = carebear.getAgentDetailsPage2;
    carebear.pageArgument = agent;
    //console.log('Get agent '+agent.id);
    $('#outputDiv').html('');
    $('#extraButtons').html(agent.name+', <span class="total"></span>');
    $("#pageSelection input:checked").removeAttr('checked').button('refresh');
    $('#outputDiv').append('<p class="detailsHeader">'+Lang.timeline+'</p><div id="agentDetailsTimeline"><div class="loading" style="display: block;"><img src="images/'+carebear.loaderImage+'.gif" width="31" height="31" /></div></div>');
    carebear.fetchData(carebear.showAgentDetailsTimeline, {act: 'ByDay', agent: agent.id});
    $('#outputDiv').append('<p class="detailsHeader">'+Lang.characters+'</p><div id="agentDetailsPlayers"><div class="loading" style="display: block;"><img src="images/'+carebear.loaderImage+'.gif" width="31" height="31" /></div></div>');
    carebear.fetchData(carebear.showAgentDetailsPlayers, {act: 'ByCharacter', agent: agent.id});
  },
  showAgentDetailsTimeline: function(data)
  {
    $("#extraButtons .total").text($().number_format(data.totalSum, {numberOfDecimals: 0, thousandSeparator: Lang.thousandSeparator, decimalSeparator: Lang.decimalSeparator}) + ' ISK')
    carebear.showGraph($('#agentDetailsTimeline'), data, true);
  },
  showAgentDetailsPlayers: function(data)
  {
    data.iDisplayLength = 10;
    data.aoColumns[1].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getPlayerDetailsPage({id: '+oObj.aData[2]+', name: \''+htmlentities(oObj.aData[1], 'ENT_QUOTES')+'\'})">'+oObj.aData[1]+'</span>';
    };
    $('#agentDetailsPlayers').html('<table class="display"></table>');
    $('#agentDetailsPlayers table').dataTable(data);
  },
  activateByRatPage: function(data)
  {
    carebear.optionsExtraHolder = {};
    $('#loading').hide();
    $('#outputDiv').html('<table id="dataTable" class="display"></table>');
    $("#extraButtons").html(Lang.total+' '+$().number_format(data.totalSum, {numberOfDecimals: 0, thousandSeparator: Lang.thousandSeparator, decimalSeparator: Lang.decimalSeparator})+" ISK");
    data.aoColumns[0].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getRatDetailsPage({id: '+oObj.aData[1]+', name: \''+htmlentities(oObj.aData[0], 'ENT_QUOTES')+'\'})">'+oObj.aData[0]+'</span>';
    };
    $('#dataTable').dataTable(data);
  },
  getRatDetailsPage: function(rat)
  {
    carebear.setHistoryData(carebear.getRatDetailsPage2, rat, null, carebear.dateRangePickers.from.datepicker('getDate'), carebear.dateRangePickers.to.datepicker('getDate'));
    //carebear.getRatDetailsPage2(rat);
  },
  getRatDetailsPage2: function(rat)
  {
    carebear.currentPage = null;
    carebear.pageFunction = carebear.getRatDetailsPage2;
    carebear.pageArgument = rat;
    //console.log('Get rat '+rat.id);
    $('#outputDiv').html('');
    $('#extraButtons').html(rat.name+', <span class="total"></span>');
    $("#pageSelection input:checked").removeAttr('checked').button('refresh');
    $('#outputDiv').append('<p class="detailsHeader">'+Lang.timeline+'</p><div id="ratDetailsTimeline"><div class="loading" style="display: block;"><img src="images/'+carebear.loaderImage+'.gif" width="31" height="31" /></div></div>');
    carebear.fetchData(carebear.showRatDetailsTimeline, {act: 'ByDay', rat: rat.id});
    $('#outputDiv').append('<p class="detailsHeader">'+Lang.characters+'</p><div id="ratDetailsPlayers"><div class="loading" style="display: block;"><img src="images/'+carebear.loaderImage+'.gif" width="31" height="31" /></div></div>');
    carebear.fetchData(carebear.showRatDetailsPlayers, {act: 'ByCharacter', rat: rat.id});
    $('#outputDiv').append('<p class="detailsHeader">'+Lang.systems+'</p><div id="ratDetailsSystems"><div class="loading" style="display: block;"><img src="images/'+carebear.loaderImage+'.gif" width="31" height="31" /></div></div>');
    carebear.fetchData(carebear.showRatDetailsSystems, {act: 'BySystem', rat: rat.id});
  },
  showRatDetailsTimeline: function(data)
  {
    var totalSumUnit = ' '+Lang.units;
    if(data.totalSum == 1)
      totalSumUnit = ' '+Lang.unit;
    $("#extraButtons .total").text($().number_format(data.totalSum, {numberOfDecimals: 0, thousandSeparator: Lang.thousandSeparator, decimalSeparator: Lang.decimalSeparator}) + totalSumUnit);
    carebear.showGraph($('#ratDetailsTimeline'), data, true, true);
  },
  showRatDetailsPlayers: function(data)
  {
    data.iDisplayLength = 10;
    data.aoColumns[1].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getPlayerDetailsPage({id: '+oObj.aData[2]+', name: \''+htmlentities(oObj.aData[1], 'ENT_QUOTES')+'\'})">'+oObj.aData[1]+'</span>';
    };
    $('#ratDetailsPlayers').html('<table class="display"></table>');
    $('#ratDetailsPlayers table').dataTable(data);
  },
  showRatDetailsSystems: function(data)
  {
    data.iDisplayLength = 10;
    data.aoColumns[1].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getSystemDetailsPage({id: '+oObj.aData[2]+', name: \''+htmlentities(oObj.aData[1], 'ENT_QUOTES')+'\'})">'+oObj.aData[1]+'</span>';
    };
    data.aoColumns[4].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getRegionDetailsPage({id: '+oObj.aData[5]+', name: \''+htmlentities(oObj.aData[4], 'ENT_QUOTES')+'\'})">'+oObj.aData[4]+'</span>';
    };
    $('#ratDetailsSystems').html('<table class="display"></table>');
    $('#ratDetailsSystems table').dataTable(data);
  },
  activateByFancyRatPage: function(data)
  {
    carebear.optionsExtraHolder = {};
    $('#loading').hide();
    $('#outputDiv').html('<table id="dataTable" class="display"></table>');
    data.aoColumns[0].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getPlayerDetailsPage({id: '+oObj.aData[1]+', name: \''+htmlentities(oObj.aData[0], 'ENT_QUOTES')+'\'})">'+oObj.aData[0]+'</span>';
    };
    data.aoColumns[2].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getRatDetailsPage({id: '+oObj.aData[3]+', name: \''+htmlentities(oObj.aData[2], 'ENT_QUOTES')+'\'})">'+oObj.aData[2]+'</span>';
    };
    data.aoColumns[5].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getSystemDetailsPage({id: '+oObj.aData[6]+', name: \''+htmlentities(oObj.aData[5], 'ENT_QUOTES')+'\'})">'+oObj.aData[5]+'</span>';
    };
    data.aoColumns[7].fnRender = function(oObj)
    {
      return '<span class="clickable" onclick="carebear.getRegionDetailsPage({id: '+oObj.aData[8]+', name: \''+htmlentities(oObj.aData[7], 'ENT_QUOTES')+'\'})">'+oObj.aData[7]+'</span>';
    };
    $('#dataTable').dataTable(data);
  },
  showGraph: function(where, input, small, noCurrency)
  {
    var data = input.graph
    where.html('<div class="flotDiv"></div>');
    if(small === true)
      $(".flotDiv", where).addClass("small");
    
    var options = {
      xaxis: {
        mode: "time",
        monthNames: $.datepicker.regional[CBconfig.language].monthNamesShort
      },
      yaxis: {
        tickFormatter: yaxis_formatter,
        min: 0
      },
      grid: {
        markings: weekendAreas,
        hoverable: true
      },
      series: {
        lines: {show: true},
        points: {show: true},
        color: "rgb(204, 204, 204)"
      }
    };
    if(CBconfig.inEVE)
    {
      options.series.color = "rgb(80, 80, 80)";
      options.grid.markingsColor = "#2c2c2c";
      options.yaxis.color = "#ffffff";
      options.xaxis.color = "#ffffff";
    }

    var byHour = false;
    if((carebear.dateRangePickers.to.datepicker('getDate') - carebear.dateRangePickers.from.datepicker('getDate')) / 1000 / 60 / 60 / 24 < CBconfig.byHour)
      byHour = true;
    for (var i = 0; i < data.length; ++i)
    {
      if(byHour !== true)
        data[i][0] += 12 * 60* 60 * 1000;
    }
    if($(".flotDiv", where).width() > 0)
    {
      $.plot($(".flotDiv", where), [data], options);

      $(".flotDiv", where).bind("plothover", function (event, pos, item) {
        if (item) {
          if (carebear.flotPoint != item.datapoint) {
            carebear.flotPoint = item.datapoint;

            $("#tooltip").remove();

            showTooltip(item.pageX, item.pageY, item.datapoint, byHour, noCurrency);
          }
        }
        else {
          $("#tooltip").remove();
          carebear.flotPoint = null;
        }
      });
    }
    else
    {
      //console.log('Problem with graph width');
    }
  },
  fetchData: function(callback, optionsExtra)
  {
    var toDate = carebear.dateRangePickers.to.datepicker('getDate');
    toDate.setDate(toDate.getDate()+1);
    var options = {
			act: carebear.currentPage,
      from: $().toDateString(carebear.dateRangePickers.from.datepicker('getDate')),
      to: $().toDateString(toDate),
      username: carebear.careUsername,
      password: carebear.carePassword
		};
    $.extend(options, optionsExtra);
    //console.log('XHR: act: '+options.act);
    $.ajax({
			type: 'POST',
			dataType: 'json',
			cache: false,
			data: options,
      statusCode:
      {
        501: function()
        {
          alert('Error: Not Implemented');
        }
      },
			success: function(data) {
        //console.log('XHR finished act: '+data.act);
        if(data.error)
        {
          alert(data.error);
        }
        else
        {
          if(callback !== undefined) {
            callback(data);
          }
        }
			}
		});
  }
};

$(document).ready(
  function()
  {
    $.fn.wait = function(time, type) {
			time = time || 1000;
			type = type || "fx";
			return this.queue(type, function() {
				var self = this;
				setTimeout(function() {
					$(self).dequeue();
				}, time);
			});
		};
    $.fn.toDateString = function(d)
    {
      function pad(n)
      {
        return n<10 ? '0'+n : n
      }
//      return d.getFullYear()+'-'
//      + pad(d.getMonth()+1)+'-'
//      + pad(d.getDate())+' '
//      + pad(d.getHours())+':'
//      + pad(d.getMinutes())+':'
//      + pad(d.getSeconds());
      return d.getFullYear()+'-'
      + pad(d.getMonth()+1)+'-'
      + pad(d.getDate());
    };
    carebear.initPage();
  }
);

function weekendAreas(axes) {
  var markings = [];
  var d = new Date(axes.xaxis.min);
  // go to the first Saturday
  d.setUTCDate(d.getUTCDate() - ((d.getUTCDay() + 1) % 7))
  d.setUTCSeconds(0);
  d.setUTCMinutes(0);
  d.setUTCHours(0);
  var i = d.getTime();
  do {
    // when we don't set yaxis, the rectangle automatically
    // extends to infinity upwards and downwards
    markings.push({xaxis: {from: i, to: i + 2 * 24 * 60 * 60 * 1000}});
    i += 7 * 24 * 60 * 60 * 1000;
  } while (i < axes.xaxis.max);

  return markings;
}

function showTooltip(x, y, contents, byHour, noCurrency) {
  function pad(n)
  {
    return n<10 ? '0'+n : n
  }
  var dateS = new Date(contents[0]);
  var cssOptions = {
    position: 'absolute',
    display: 'none',
    top: y + 10,
    padding: '2px',
    opacity: 0.80
  };
  var extra = {};
  var left = (x + 10);
  if(left > $(window).width() - 100)
    extra.right = $(window).width() - x + 10;
  else
    extra.left = (x + 10);
  $.extend(cssOptions, extra);
  var currency = ' ISK';
  if(noCurrency)
  {
    if(contents[1] == 1)
      currency = ' '+Lang.unit;
    else
      currency = ' '+Lang.units;
  }
  if(byHour)
    $('<div id="tooltip" class="ui-widget ui-widget-content ui-corner-all">'+dateFormat(dateS, Lang.dateFormat, true)+'<br />'+dateS.getUTCHours()+':00 - '+dateS.getUTCHours()+':59<br />' + $().number_format(contents[1], {numberOfDecimals: 0, thousandSeparator: Lang.thousandSeparator, decimalSeparator: Lang.decimalSeparator}) + currency + '</div>').css( cssOptions).appendTo("body").fadeIn(200);
  else
    $('<div id="tooltip" class="ui-widget ui-widget-content ui-corner-all">'+dateFormat(dateS, Lang.dateFormat, true)+'<br />' + $().number_format(contents[1], {numberOfDecimals: 0, thousandSeparator: Lang.thousandSeparator, decimalSeparator: Lang.decimalSeparator}) + currency + '</div>').css( cssOptions).appendTo("body").fadeIn(200);
}

function yaxis_formatter(val, axis)
{
  return $().number_format(val, {numberOfDecimals: axis.tickDecimals, thousandSeparator: Lang.thousandSeparator, decimalSeparator: Lang.decimalSeparator})
}

function executeFunction(functionName, argument)
{
  functionName(argument);
}

function get_html_translation_table (table, quote_style) {
    // http://kevin.vanzonneveld.net
    // +   original by: Philip Peterson
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: noname
    // +   bugfixed by: Alex
    // +   bugfixed by: Marco
    // +   bugfixed by: madipta
    // +   improved by: KELAN
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Frank Forte
    // +   bugfixed by: T.Wild
    // +      input by: Ratheous
    // %          note: It has been decided that we're not going to add global
    // %          note: dependencies to php.js, meaning the constants are not
    // %          note: real constants, but strings instead. Integers are also supported if someone
    // %          note: chooses to create the constants themselves.
    // *     example 1: get_html_translation_table('HTML_SPECIALCHARS');
    // *     returns 1: {'"': '&quot;', '&': '&amp;', '<': '&lt;', '>': '&gt;'}
    var entities = {},
        hash_map = {},
        decimal = 0,
        symbol = '';
    var constMappingTable = {},
        constMappingQuoteStyle = {};
    var useTable = {},
        useQuoteStyle = {};

    // Translate arguments
    constMappingTable[0] = 'HTML_SPECIALCHARS';
    constMappingTable[1] = 'HTML_ENTITIES';
    constMappingQuoteStyle[0] = 'ENT_NOQUOTES';
    constMappingQuoteStyle[2] = 'ENT_COMPAT';
    constMappingQuoteStyle[3] = 'ENT_QUOTES';

    useTable = !isNaN(table) ? constMappingTable[table] : table ? table.toUpperCase() : 'HTML_SPECIALCHARS';
    useQuoteStyle = !isNaN(quote_style) ? constMappingQuoteStyle[quote_style] : quote_style ? quote_style.toUpperCase() : 'ENT_COMPAT';

    if (useTable !== 'HTML_SPECIALCHARS' && useTable !== 'HTML_ENTITIES') {
        throw new Error("Table: " + useTable + ' not supported');
        // return false;
    }

    entities['38'] = '&amp;';
    if (useTable === 'HTML_ENTITIES') {
        entities['160'] = '&nbsp;';
        entities['161'] = '&iexcl;';
        entities['162'] = '&cent;';
        entities['163'] = '&pound;';
        entities['164'] = '&curren;';
        entities['165'] = '&yen;';
        entities['166'] = '&brvbar;';
        entities['167'] = '&sect;';
        entities['168'] = '&uml;';
        entities['169'] = '&copy;';
        entities['170'] = '&ordf;';
        entities['171'] = '&laquo;';
        entities['172'] = '&not;';
        entities['173'] = '&shy;';
        entities['174'] = '&reg;';
        entities['175'] = '&macr;';
        entities['176'] = '&deg;';
        entities['177'] = '&plusmn;';
        entities['178'] = '&sup2;';
        entities['179'] = '&sup3;';
        entities['180'] = '&acute;';
        entities['181'] = '&micro;';
        entities['182'] = '&para;';
        entities['183'] = '&middot;';
        entities['184'] = '&cedil;';
        entities['185'] = '&sup1;';
        entities['186'] = '&ordm;';
        entities['187'] = '&raquo;';
        entities['188'] = '&frac14;';
        entities['189'] = '&frac12;';
        entities['190'] = '&frac34;';
        entities['191'] = '&iquest;';
        entities['192'] = '&Agrave;';
        entities['193'] = '&Aacute;';
        entities['194'] = '&Acirc;';
        entities['195'] = '&Atilde;';
        entities['196'] = '&Auml;';
        entities['197'] = '&Aring;';
        entities['198'] = '&AElig;';
        entities['199'] = '&Ccedil;';
        entities['200'] = '&Egrave;';
        entities['201'] = '&Eacute;';
        entities['202'] = '&Ecirc;';
        entities['203'] = '&Euml;';
        entities['204'] = '&Igrave;';
        entities['205'] = '&Iacute;';
        entities['206'] = '&Icirc;';
        entities['207'] = '&Iuml;';
        entities['208'] = '&ETH;';
        entities['209'] = '&Ntilde;';
        entities['210'] = '&Ograve;';
        entities['211'] = '&Oacute;';
        entities['212'] = '&Ocirc;';
        entities['213'] = '&Otilde;';
        entities['214'] = '&Ouml;';
        entities['215'] = '&times;';
        entities['216'] = '&Oslash;';
        entities['217'] = '&Ugrave;';
        entities['218'] = '&Uacute;';
        entities['219'] = '&Ucirc;';
        entities['220'] = '&Uuml;';
        entities['221'] = '&Yacute;';
        entities['222'] = '&THORN;';
        entities['223'] = '&szlig;';
        entities['224'] = '&agrave;';
        entities['225'] = '&aacute;';
        entities['226'] = '&acirc;';
        entities['227'] = '&atilde;';
        entities['228'] = '&auml;';
        entities['229'] = '&aring;';
        entities['230'] = '&aelig;';
        entities['231'] = '&ccedil;';
        entities['232'] = '&egrave;';
        entities['233'] = '&eacute;';
        entities['234'] = '&ecirc;';
        entities['235'] = '&euml;';
        entities['236'] = '&igrave;';
        entities['237'] = '&iacute;';
        entities['238'] = '&icirc;';
        entities['239'] = '&iuml;';
        entities['240'] = '&eth;';
        entities['241'] = '&ntilde;';
        entities['242'] = '&ograve;';
        entities['243'] = '&oacute;';
        entities['244'] = '&ocirc;';
        entities['245'] = '&otilde;';
        entities['246'] = '&ouml;';
        entities['247'] = '&divide;';
        entities['248'] = '&oslash;';
        entities['249'] = '&ugrave;';
        entities['250'] = '&uacute;';
        entities['251'] = '&ucirc;';
        entities['252'] = '&uuml;';
        entities['253'] = '&yacute;';
        entities['254'] = '&thorn;';
        entities['255'] = '&yuml;';
    }

    if (useQuoteStyle !== 'ENT_NOQUOTES') {
        entities['34'] = '&quot;';
    }
    if (useQuoteStyle === 'ENT_QUOTES') {
        entities['39'] = '&#39;';
    }
    entities['60'] = '&lt;';
    entities['62'] = '&gt;';


    // ascii decimals to real symbols
    for (decimal in entities) {
        symbol = String.fromCharCode(decimal);
        hash_map[symbol] = entities[decimal];
    }

    return hash_map;
}

function htmlentities (string, quote_style) {
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: nobbler
    // +    tweaked by: Jack
    // +   bugfixed by: Onno Marsman
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Ratheous
    // -    depends on: get_html_translation_table
    // *     example 1: htmlentities('Kevin & van Zonneveld');
    // *     returns 1: 'Kevin &amp; van Zonneveld'
    // *     example 2: htmlentities("foo'bar","ENT_QUOTES");
    // *     returns 2: 'foo&#039;bar'
    var hash_map = {},
        symbol = '',
        tmp_str = '',
        entity = '';
    tmp_str = string.toString();

    if (false === (hash_map = get_html_translation_table('HTML_ENTITIES', quote_style))) {
        return false;
    }
    hash_map["'"] = '&#039;';
    for (symbol in hash_map) {
        entity = hash_map[symbol];
        tmp_str = tmp_str.split(symbol).join(entity);
    }

    return tmp_str;
}
