<template>
    <tr>
        <td :style="{ paddingLeft: `${depth * 20}px` }" class="px-4 py-2 border border-gray-200">
            {{ account.name || 'No Account Name' }}
        </td>
        <td class="px-4 py-2 border border-gray-200 text-right" 
            :class="{ 'text-rose-600': isNegative(account.balance) }">
            {{ formatBalance(account.balance) }}
        </td>
    </tr>

    <!-- Loop untuk nested children -->
    <template v-if="account.children && account.children.length > 0">
        <BalanceSheetRow 
            v-for="(child, childIndex) in account.children" 
            :key="childIndex"
            :account="child"
            :depth="depth + 1"
        />
    </template>
</template>

<script>
export default {
    name: 'BalanceSheetRow',
    props: {
        account: Object,
        depth: {
            type: Number,
            default: 1
        }
    },
    methods: {
        isNegative(value) {
            return parseFloat(value) < 0;
        },
        formatBalance(value) {
            if (value === null || value === undefined) return '-';
            return new Intl.NumberFormat('id-ID', { 
                minimumFractionDigits: 2, 
                maximumFractionDigits: 2 
            }).format(parseFloat(value));
        }
    }
}
</script>
