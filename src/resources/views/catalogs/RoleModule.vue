<template>
    <div :class="color">
        <div class="card-header">
            {{ title }}
            <div class="card-tools">
                <button type="button" class="btn btn-tool" @click="set(true)">
                    <i class="far fa-check-square"></i>
                </button>
                <button type="button" class="btn btn-tool" @click="set(false)">
                    <i class="far fa-square"></i>
                </button>
                <button
                    type="button"
                    class="btn btn-tool"
                    data-card-widget="collapse"
                >
                    <i class="fa fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <template v-for="mp in modulepermissions">
                <div class="form-check" v-if="shouldShow(mp)" :key="mp.name">
                    <input
                        :id="mp.name"
                        class="form-check-input"
                        type="checkbox"
                        v-model="mp.enabled"
                    />
                    <label :for="mp.name" class="form-check-label">{{
                        mp.p.description
                    }}</label>
                </div>
            </template>
        </div>
        <div class="card-footer">
            <a href="javascript:void(0);" @click="set(true)">Todos</a> |
            <a href="javascript:void(0);" @click="set(false)">Ninguno</a>
        </div>
    </div>
</template>
<script>
export default {
    props: ["modulepermissions", "title", "rolemodulepermissions"],
    methods: {
        set: function (bool) {
            this.modulepermissions.map((mp) => {
                mp.enabled = bool;
            });
        },
        shouldShow: function (mp) {
            return !mp.p.parent || !this.allPermissions.includes(mp.p.parent);
        },
    },
    computed: {
        allPermissions: function () {
            if (!this.modulepermissions) {
                return [];
            }
            return this.modulepermissions.map((mp) => mp.permission);
        },
        color: function () {
            var clase = "card collapsed-card card-outline ";

            if (!this.modulepermissions) {
                return clase;
            }
            let all = this.modulepermissions.reduce((carry, mp) => {
                if (!mp.p.parent_id) {
                    return carry + 1;
                }
                return carry;
            }, 0);

            let selected = this.modulepermissions.reduce((carry, mp) => {
                if (mp.enabled && !mp.p.parent_id) {
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
<style scoped>
.btn-tool {
    padding: 0.1rem;
}
</style>
