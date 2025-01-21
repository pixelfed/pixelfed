setup() {
    DIR="$(cd "$(dirname "${BATS_TEST_FILENAME:-}")" >/dev/null 2>&1 && pwd)"
    ROOT="$(dirname "$(dirname "$DIR")")"

    load "$ROOT/docker/shared/root/docker/helpers.sh"
}

teardown() {
    if [[ -e test_dir ]]; then
        rm -rf test_dir
    fi
}

@test "test [is-true]" {
    is-true "1"
    is-true "true"
    is-true "TrUe"
}

@test "test [is-false]" {
    is-false "0"
    is-false "false"
    is-false "FaLsE"
}

@test "test [is-false-expressions-0]" {
    if is-false "0"; then
        return 0
    fi

    return 1
}

@test "test [is-false-expressions-false]" {
    if is-false "false"; then
        return 0
    fi

    return 1
}

@test "test [is-false-expressions-FaLse]" {
    if is-false "FaLse"; then
        return 0
    fi

    return 1
}

@test "test [is-false-expressions-invalid]" {
    if is-false "invalid"; then
        return 0
    fi

    return 1
}

@test "test [is-true-expressions-1]" {
    if is-true "1"; then
        return 0
    fi

    return 1
}

@test "test [is-true-expressions-true]" {
    if is-true "true"; then
        return 0
    fi

    return 1
}

@test "test [is-true-expressions-TrUE]" {
    if is-true "TrUE"; then
        return 0
    fi

    return 1
}

@test "test [directory-is-empty] - non existing" {
    directory-is-empty test_dir
}

@test "test [directory-is-empty] - actually empty" {
    mkdir -p test_dir

    directory-is-empty test_dir
}

@test "test [directory-is-empty] - not empty (directory)" {
    mkdir -p test_dir/sub-dir

    ! directory-is-empty test_dir
}

@test "test [directory-is-empty] - not empty (file)" {
    mkdir -p test_dir/
    touch test_dir/hello-world.txt

    ! directory-is-empty test_dir
}
