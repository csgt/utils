<template>
    <div class="card" :class="color">
        <div class="card-header">
            <span>{{ module.description }}</span>
            <div class="card-tools">
                <button class="btn btn-tool" @click="set(false)">
                    <i class="fa-regular fa-square text-danger"></i>
                </button>
                <button class="btn btn-tool" @click="set(true)">
                    <i class="fa-regular fa-square-check text-success"></i>
                </button>
                <button
                    type="button"
                    class="btn btn-tool"
                    data-bs-toggle="collapse"
                    :data-bs-target="'#collapse_body' + module.name"
                >
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body collapse" :id="'collapse_body' + module.name">
            <div class="form-check" v-for="mp in module.modulepermissions" :key="mp.name">
                <input :id="mp.name" class="form-check-input" type="checkbox" v-model="mp.enabled" />
                <label :for="mp.name" class="form-check-label">{{ mp.description }}</label>
            </div>
        </div>
    </div>
    <br />
</template>

<script>
export default {
    props: {
        module: null,
    },
    methods: {
        set(bool) {
            this.module.modulepermissions.forEach((mp) => {
                mp.enabled = bool;
            });
        },
    },
    computed: {
        color() {
            let clase = "card-header";

            if (!this.module) {
                return clase;
            }

            let all = this.module.modulepermissions.reduce((carry, mp) => {
                return carry + 1;
            }, 0);

            let selected = this.module.modulepermissions.reduce((carry, mp) => {
                return carry + (mp.enabled ? 1 : 0);
            }, 0);

            if (all === selected) {
                return `border-success`;
            } else if (selected === 0) {
                return `border-danger`;
            }
            return `border-warning`;
        },
    },
};
</script>
