function layeralert(title, text) { layui.use('layer', function () { var layer = layui.layer; layer.open({ title: title, content: text, btn: ['OK'] }) }) }
function layeralert2(title, text, url) { layui.use('layer', function () { var layer = layui.layer; layer.open({ title: title, content: text, btn: ['OK'], yes: function () { linkto(url); } }) }) }
function linkto(url) { location.href = url; }

var emailRule = /^([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|\_|\.|-|\-]?)*[a-zA-Z0-9]+\.[a-zA-Z]{2,3}$/;
var phoneRule = /^(13|14|15|16|17|18|19)[0-9]{9}$/;
var telRule = /^0\d{2,3}-?\d{7,8}$/;
var passRule = /^(?![0-9]+$)(?![a-z]+$)(?![A-Z]+$)(?!([^(0-9a-zA-Z)])+$).{8,16}$/;


$(function () {
    if ($('.goodsinfo-wrapper').length > 0) {
        var num = $('.goodsinfo-wrapper .box-2').find('.item').length
        if (num <=0) {
            $('.goodsinfo-wrapper .return').addClass('return2')
        }
    }
})



function search1() {
    if (document.getElementById("txtkeys1").value == "") {
        layeralert("Message", "Please enter keywords！");
    }
    else {
        location.href = "/search.aspx?k=" + document.getElementById("txtkeys1").value;
    }
}

function search2() {
    if (document.getElementById("txtkeys2").value == "") {
        layeralert("Message", "Please enter keywords！");
    }
    else {
        location.href = "/search.aspx?k=" + document.getElementById("txtkeys2").value;
    }
}

$(function () {
    $("#txtkeys1").keydown(function (e) {
        if (e.keyCode == 13) {
            search1();
        }
    });
    $("#txtkeys2").keydown(function (e) {
        if (e.keyCode == 13) {
            search2();
        }
    });
})

function addmessage(m) {
    var name = $("#name").val();
    var tel = $("#tel").val();
    var email = $("#email").val();
    var content = $("#intro").val();

    if (name == "") {
        layeralert("Message", "Please enter your name!");
        return
    }

    if (tel == "" && email == "") {
        layeralert("Message", "Please enter your name telephone/email!");
        return
    }

    //if (tel != "" && !telRule.test(tel) && !phoneRule.test(tel)) {
    //    layeralert('Message', 'Please enter the correct format telephone！');
    //    return false;
    //}

    //if (email == "") {
    //    layeralert("提示", "请填写您的邮箱!");
    //    return
    //}

    if (email != "" && !emailRule.test(email)) {
        layeralert('Message', 'Please enter the correct format email!');
        return false;
    }


    if (content == "") {
        layeralert('Message', 'Please enter content！');
        return false;
    }


    $.ajax({
        type: "post",
        url: "/AjaxAction/message.ashx?action=addmessage&randroom=" + Math.random(),
        dataType: "JSON",
        data: {
            "name": name,
            "tel": tel,
            "email": email,
            "m": m,
            "content": content
        },
        success: function (data) {
            if (data.status == 1) {
                layeralert2("Message", "Congratulations to you to submit it successfully!", window.location.href);
            }
            else {
                layeralert("Message", data.msg);
            }

        }
    });

}
