<template>
    <div :class="color">
        <div class="card-header">
            {{module.description}}
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fa fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <template v-for="mp in module.modulepermissions">
                <div class="form-check" v-if="shouldShow(mp)" :key="mp.id">
                    <input :id="mp.permission.name + '-' + module.id" class="form-check-input" type="checkbox" v-model="mp.enabled">
                    <label :for="mp.permission.name + '-' + module.id" class="form-check-label">{{mp.permission.description}}</label>
                </div>
            </template>
        </div>
        <div class="card-footer">
            <a href="javascript:void(0);" @click="set(true)">Todos</a> | <a href="javascript:void(0);" @click="set(false)">Ninguno</a>
        </div>
    </div>
</template>
<script>
export default {
    props: {
        module: null,
    },
    methods: {
        set: function (bool) {
            this.module.modulepermissions.map((mp) => {
                mp.enabled = bool;
            });
        },
        shouldShow: function (mp) {
            return (
                !mp.permission.parent_id ||
                !this.allPermissions.includes(mp.permission.parent_id)
            );
        },
    },
    computed: {
        allPermissions: function () {
            if (!module.modulepermissions) {
                return [];
            }
            return module.modulepermissions.map((mp) => mp.permission.id);
        },
        color: function () {
            var clase = "card collapsed-card card-outline ";

            if (!this.module) {
                return clase;
            }
            let all = this.module.modulepermissions.reduce((carry, mp) => {
                if (!mp.permission.parent_id) {
                    return carry + 1;
                }
                return carry;
            }, 0);

            let selected = this.module.modulepermissions.reduce((carry, mp) => {
                if (mp.enabled && !mp.permission.parent_id) {
                    return carry + 1;
                }
                return carry;
            }, 0);

            if (all == selected) {
                return clase + "card-success";
            } else if (selected == 0) {
                return clase + "card-danger";
            }
            return clase + "card-warning";
        },
    },
};
</script>
