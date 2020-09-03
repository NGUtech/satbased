function (commit) {
    if (/^satbased\.security\.profile\-/.test(commit._id) && commit.sequence) {
        emit(commit.committedAt, 1);
    }
}