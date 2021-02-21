layui.define( [ 'element','form','laytpl'],function(exports) {
    var element = layui.element, laytpl = layui.laytpl, $ =layui.jquery,laytpl=layui.laytpl, authtree = layui.authtree,table = layui.table,mdocument=layui.mdocument;

    var mdocument = {
        data:{
            nav:{},
            dataStructure:{},
        },
        /**
         * 初始化
         * @param comfig
         */
        render:function (comfig={}) {
            console.log(comfig)
            // 请求后端获取对应的数据规格
            this.initData(comfig.prefix)
            // 请求后端获取菜单数据
            //  请求后端获取后端版本
            //  实例化渲染导航菜单
        },
        req:function(config = {type:'GET',dataType:'json'}) {
            let obj
            $.ajax({//异步请求返回给后台
                url:'/'+prefix+'/document/init-data.json',
                type:'GET',
                async:false,
                data:{},
                dataType:'json',
                success:function(data){
                    //把得到的数据

                }
            });
        },
        initData:function (prefix) {
            $.ajax({//异步请求返回给后台
                url:'/'+prefix+'/document/init-data.json',
                type:'GET',
                async:false,
                data:{},
                dataType:'json',
                success:function(data){
                    //把得到的数据
                    mdocument.data.dataStructure = data.dataStructure
                    mdocument.data.nav = data.nav
                }
            });
        }
    }
    exports('mdocument', mdocument)
})