<template>
    <div class="member-only-warning">
        <div class="member-only-warning-wrapper">
            <h3>Content unavailable</h3>
            <p>You need to join this Group before you can access this content.</p>
        </div>
    </div>
</template>

<style lang="scss" scoped>
    .member-only-warning {
        display: flex;
        justify-content: center;

        &-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            width: 100%;
            max-width: 550px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            padding: 4rem 1rem;


            h3 {
                font-weight: bold;
                letter-spacing: -1px;
            }

            p {
                font-size: 1.2em;
                margin-bottom: 0px;
            }
        }
    }
</style>
